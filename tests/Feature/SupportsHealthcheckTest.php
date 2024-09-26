<?php

namespace JkOster\CronMonitor\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use JkOster\CronMonitor\Models\CronMonitor;
use JkOster\CronMonitor\Models\Enums\CronMonitorStatus;
use JkOster\CronMonitor\Models\Enums\IncomingPingStatus;
use JkOster\CronMonitor\Tests\TestCase;

class SupportsCronMonitorTest extends TestCase
{
    use RefreshDatabase;

    /** @var CronMonitor */
    protected $monitor;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->monitor = new class extends CronMonitor
        {
            public $enabled;

            public $status;

            public $last_check_date;

            public $frequency_in_minutes;

            public $grace_period_in_minutes;

            public $name;

            public $failure_reason;

            public $last_check_failed_date;

            public $cron_expression;

            public $next_due_date;

            public $timezone;

            public function save(array $options = [])
            {
                return $this;
            }

            public function update(array $attributes = [], array $options = [])
            {
                foreach ($attributes as $key => $value) {
                    $this->$key = $value;
                }

                return $this;
            }

            // Override any methods that might cause database interactions
            public static function bootSupportsCronMonitor(): void {}
        };

        $this->monitor->enabled = true;
        $this->monitor->status = CronMonitorStatus::UP;
        $this->monitor->last_check_date = now()->subMinutes(5);
        $this->monitor->frequency_in_minutes = 10;
        $this->monitor->grace_period_in_minutes = 5;
        $this->monitor->name = 'Test CronMonitor';
        $this->monitor->timezone = 'UTC';
    }

    public function testShouldCheckDown()
    {
        // Test when CronMonitor is enabled and next_due_date is in the past
        $this->monitor->enabled = true;
        $this->monitor->next_due_date = now()->subMinutes(1);
        $this->assertTrue($this->monitor->shouldCheckDown());

        // Test when CronMonitor is enabled but next_due_date is in the future
        $this->monitor->next_due_date = now()->addMinutes(1);
        $this->assertFalse($this->monitor->shouldCheckDown());

        // Test when CronMonitor is disabled
        $this->monitor->enabled = false;
        $this->assertFalse($this->monitor->shouldCheckDown());

        // Test when CronMonitor is down
        $this->monitor->enabled = true;
        $this->monitor->status = CronMonitorStatus::DOWN;
        $this->assertFalse($this->monitor->shouldCheckDown());
    }

    public function testCheckHealthStatus()
    {
        // Test UNKNOWN status when last_check is null
        $this->monitor->last_check_date = null;
        $this->assertEquals(CronMonitorStatus::UNKNOWN, $this->monitor->checkHealthStatus());

        // Test UP status
        $this->monitor->last_check_date = now();
        $this->monitor->next_due_date = now()->addMinutes(1);
        $this->monitor->status = CronMonitorStatus::UP;
        $this->assertEquals(CronMonitorStatus::UP, $this->monitor->checkHealthStatus());

        // Test DOWN status when exceeding frequency
        $this->monitor->next_due_date = now()->subMinutes(61);
        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->checkHealthStatus());

        // Test grace period
        $this->monitor->status = CronMonitorStatus::STARTED;
        $this->monitor->next_due_date = now()->addMinutes(5);
        $this->assertEquals(CronMonitorStatus::STARTED, $this->monitor->checkHealthStatus());

        // Test exceeding grace period
        $this->monitor->next_due_date = now()->subMinutes(11);
        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->checkHealthStatus());

        // Test with cron expression
        $this->monitor->cron_expression = '*/5 * * * *';
        $this->monitor->next_due_date = now()->addMinutes(2);
        $this->monitor->status = CronMonitorStatus::UP;
        $this->assertEquals(CronMonitorStatus::UP, $this->monitor->checkHealthStatus());

        // Test cron expression with exceeded grace period
        $this->monitor->next_due_date = now()->subMinutes(7);
        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->checkHealthStatus());

        // Test status doesn't change from DOWN to UP automatically
        $this->monitor->status = CronMonitorStatus::DOWN;
        $this->monitor->next_due_date = now()->addMinutes(2);
        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->checkHealthStatus());
    }

    public function testCronMonitorStatusReceived()
    {
        $request = new Request;

        $this->monitor->status = CronMonitorStatus::DOWN;
        $this->monitor->next_due_date = now()->subMinutes(10);

        $this->monitor->cronMonitorStatusReceived(IncomingPingStatus::SUCCESS, $request);
        $this->assertEquals(CronMonitorStatus::UP, $this->monitor->status);
        $this->assertNotNull($this->monitor->last_check_date);
        $this->assertTrue($this->monitor->next_due_date->isFuture());
        $this->assertEquals(IncomingPingStatus::SUCCESS, $this->monitor->last_ping_status);

        // Test ERROR status

        $this->monitor->status = CronMonitorStatus::UP;
        $this->monitor->next_due_date = now();

        $this->monitor->cronMonitorStatusReceived(IncomingPingStatus::ERROR, $request);
        $this->assertNotTrue($this->monitor->next_due_date->isFuture());
        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->status);
        $this->assertEquals(IncomingPingStatus::ERROR, $this->monitor->last_ping_status);

        // Test STARTED status
        $this->monitor->status = CronMonitorStatus::UP;
        $this->monitor->next_due_date = now();

        $this->monitor->cronMonitorStatusReceived(IncomingPingStatus::STARTED, $request);
        $this->assertTrue($this->monitor->next_due_date->isFuture());
        $this->assertEquals(CronMonitorStatus::STARTED, $this->monitor->status);
        $this->assertEquals(IncomingPingStatus::STARTED, $this->monitor->last_ping_status);

        // Test UNKNOWN status
        $this->monitor->status = CronMonitorStatus::UP;
        $this->monitor->next_due_date = now();

        $this->monitor->cronMonitorStatusReceived(IncomingPingStatus::UNKNOWN, $request);
        $this->assertEquals(CronMonitorStatus::UNKNOWN, $this->monitor->status);
        $this->assertEquals(IncomingPingStatus::UNKNOWN, $this->monitor->last_ping_status);

        // Test status change from DOWN to UP
        $this->monitor->status = CronMonitorStatus::UP;
        $this->monitor->next_due_date = now();

        $this->monitor->status = CronMonitorStatus::DOWN;
        $this->monitor->cronMonitorStatusReceived(IncomingPingStatus::SUCCESS, $request);
        $this->assertEquals(CronMonitorStatus::UP, $this->monitor->status);

        // Test with request containing failure reason
        $requestWithReason = new Request(['reason' => 'Test failure', 'status' => IncomingPingStatus::ERROR]);
        $this->monitor->cronMonitorStatusReceived(IncomingPingStatus::ERROR, $requestWithReason);
        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->status);
        $this->assertEquals('Test failure', $this->monitor->failure_reason);
    }

    public function testCronMonitorHasFailed()
    {
        $request = new Request(['reason' => 'Test failure', 'status' => IncomingPingStatus::ERROR]);
        $this->monitor->cronMonitorHasFailed($request);

        $this->assertEquals(CronMonitorStatus::DOWN, $this->monitor->status);
        $this->assertEquals('Test failure', $this->monitor->failure_reason);
        $this->assertNotNull($this->monitor->last_check_failed_date);
    }

    public function testCronMonitorHasRecovered()
    {
        $this->monitor->status = CronMonitorStatus::DOWN;
        $this->monitor->last_check_failed_date = now()->subMinutes(10);
        $this->monitor->cronMonitorHasRecovered();

        $this->assertEquals(CronMonitorStatus::UP, $this->monitor->status);
    }
}
