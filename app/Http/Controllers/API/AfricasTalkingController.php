<?php

namespace App\Http\Controllers\API;

use AfricasTalking\SDK\AfricasTalking;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

// Route::group([
//     'prefix' => 'v1/communications',
//     'middleware' => [
//         VerifyAfricasTalkingWebhook::class,
//         'throttle:api-webhook',
//     ],
//     'as' => 'api.communications.',
// ], function () {
//     Route::post('/africa-is-talking/entrypoint', [AfricasTalkingController::class, 'entrypoint'])->name('entrypoint');
//     Route::post('/africa-is-talking/route-call', [AfricasTalkingController::class, 'routeCall'])->name('route-call');
//     Route::post('/africa-is-talking/call-from-missions', [AfricasTalkingController::class, 'callFromMissions'])->name('call-missions');
//     Route::post('/africa-is-talking/call-from-os', [AfricasTalkingController::class, 'callFromOS'])->name('call-os');
// });

class AfricasTalkingController extends Controller
{
    // public function entrypoint(Request $request)
    // {
    //     $validated = $request->all();

    //     Log::info('Africas Talking Controller || Index || ', $validated);
    //     Log::info($validated['isActive'], ['isActive' => $validated['isActive'] == '1']);

    //     if ($validated['isActive'] === '1') {
    //         return $this->_requestDigits();
    //     }
    // }

    // private function _requestDigits()
    // {
    //     $at = new AfricasTalking(
    //         username: config('prf.app.africas_talking.username'),
    //         apiKey: config('prf.app.africas_talking.api_key')
    //     );

    //     $greetings = 'Hello, welcome to Parkroad Fellowship (PRF). Please enter 1 for Missions, 2 for News and then press hash';

    //     $voice = $at->voice();
    //     $voiceActions = $voice->messageBuilder();

    //     $xmlResponse = $voiceActions
    //         ->getDigits([
    //             'text' => $greetings,
    //             'numDigits' => 1,
    //             'timeout' => 10,
    //             'finishOnKey' => '#',
    //             'callBackUrl' => config('prf.app.africas_talking.callback_url').'/api/v1/communications/africa-is-talking/route-call',
    //         ])->build();

    //     return response($xmlResponse, 200)->header('Content-Type', 'text/plain');
    // }

    // public function routeCall(Request $request)
    // {
    //     $validated = $request->all();
    //     Log::info('Africas Talking Controller || RouteCall || ', $validated);

    //     return $this->_routeCall($validated);
    // }

    // public function _routeCall(array $validated)
    // {
    //     Log::info('Africas Talking Controller || _RouteCall || ', $validated);

    //     if (Arr::has($validated, 'dtmfDigits')) {
    //         $digits = $validated['dtmfDigits'];

    //         return match ($digits) {
    //             '1' => $this->_routeToMissionsDesk($validated),
    //             '2' => $this->_routeToOS($validated),
    //             default => $this->_requestDigits(),
    //         };
    //     }
    // }

    // public function _routeToMissionsDesk(array $validated)
    // {
    //     Log::info('Africas Talking Controller || RouteMissions || ', $validated);

    //     $at = new AfricasTalking(
    //         username: config('prf.app.africas_talking.username'),
    //         apiKey: config('prf.app.africas_talking.api_key')
    //     );

    //     $voice = $at->voice();
    //     $voiceActions = $voice->messageBuilder();

    //     $xmlResponse = $voiceActions
    //         ->dial([
    //             'phoneNumbers' => [config('prf.app.africas_talking.missions_desk')],
    //             'callerId' => config('prf.app.africas_talking.from'),
    //         ])
    //         ->build();

    //     return response($xmlResponse, 200)->header('Content-Type', 'text/plain');
    // }

    // public function _routeToOS(array $validated)
    // {
    //     Log::info('Africas Talking Controller || RouteOS || ', $validated);

    //     $at = new AfricasTalking(
    //         username: config('prf.app.africas_talking.username'),
    //         apiKey: config('prf.app.africas_talking.api_key')
    //     );

    //     $voice = $at->voice();
    //     $voiceActions = $voice->messageBuilder();

    //     $xmlResponse = $voiceActions
    //         ->dial([
    //             'phoneNumbers' => [config('prf.app.africas_talking.os_desk')],
    //             'callerId' => config('prf.app.africas_talking.from'),
    //         ])
    //         ->build();

    //     return response($xmlResponse, 200)->header('Content-Type', 'text/plain');
    // }

    // public function callFromMissions(Request $request)
    // {
    //     $validated = $request->all();

    //     Log::info('Africas Talking Controller || CallMissions || ', $validated);

    //     $at = new AfricasTalking(
    //         username: config('prf.app.africas_talking.username'),
    //         apiKey: config('prf.app.africas_talking.api_key')
    //     );

    //     $voice = $at->voice();
    //     $voice->call(
    //         [
    //             'to' => $validated['callerNumber'],
    //             'from' => config('prf.app.africas_talking.from'),
    //         ]
    //     );
    // }

    // public function callFromOS(Request $request)
    // {
    //     $validated = $request->all();

    //     Log::info('Africas Talking Controller || CallOS || ', $validated);

    //     $at = new AfricasTalking(
    //         username: config('prf.app.africas_talking.username'),
    //         apiKey: config('prf.app.africas_talking.api_key')
    //     );

    //     $voice = $at->voice();
    //     $voice->call(
    //         [
    //             'to' => $validated['callerNumber'],
    //             'from' => config('prf.app.africas_talking.from'),
    //         ]
    //     );
    // }
}
