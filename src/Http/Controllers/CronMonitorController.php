<?php

namespace JkOster\CronMonitor\Http\Controllers;

use Illuminate\Http\Request;
use JkOster\CronMonitor\CronMonitorRepository;
use JkOster\CronMonitor\Models\Enums\IncomingPingStatus;

class CronMonitorController extends Controller
{
    public function ping(Request $request, string $hash, string $status = '')
    {
        $monitor = CronMonitorRepository::getByHash($hash);

        $status = $status !== '' ? $status : $request->input('status', IncomingPingStatus::SUCCESS);

        // if (!in_array($status, [
        //     IncomingPingStatus::SUCCESS,
        //     IncomingPingStatus::ERROR,
        //     IncomingPingStatus::UNKNOWN,
        //     IncomingPingStatus::STARTED,
        // ])) {
        //     return response()->json(['message' => 'invalid status'], 400);
        // }

        if ($monitor) {

            /** @var CronMonitor $monitor */
            $monitor->cronMonitorStatusReceived($status, $request);
            $newStatus = $monitor->checkHealthStatus();

            return response()->json(['message' => 'pong', 'status' => $newStatus], 200);
        }

        return response()->json(['message' => 'not found'], 404);
    }

    public function status(string $hash)
    {
        $monitor = CronMonitorRepository::getByHash($hash);
        if ($monitor) {
            return response()->json(['message' => $monitor->status]);
        }

        return response()->json(['message' => 'not found']);
    }
}
