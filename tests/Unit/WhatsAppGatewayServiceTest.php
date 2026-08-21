<?php

namespace Tests\Unit;

use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppGatewayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp_service', [
            'base_url' => 'http://whatsapp.test',
            'api_key' => 'test-key',
            'session' => '7000000000',
            'timeout' => 10,
        ]);
    }

    public function test_it_reads_message_history_for_a_normalized_phone(): void
    {
        Http::fake([
            'http://whatsapp.test/messages/history*' => Http::response([
                'success' => true,
                'data' => [
                    'messages' => [
                        ['messageId' => 'message-1', 'body' => 'Existing message'],
                    ],
                ],
            ]),
        ]);

        $result = app(WhatsAppGatewayService::class)
            ->getMessageHistory('+91 70356 24149', '7099481497', 50);

        $this->assertTrue($result['success']);
        $this->assertSame('Existing message', data_get($result, 'data.messages.0.body'));
        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'http://whatsapp.test/messages/history')
                && $request['to'] === '917035624149'
                && $request['channelKey'] === '7099481497'
                && (int) $request['limit'] === 50
                && $request->hasHeader('x-api-key', 'test-key');
        });
    }

    public function test_it_keeps_the_provider_response_after_sending(): void
    {
        Http::fake([
            'http://whatsapp.test/messages/send' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'provider-message-id',
                    'contentType' => 'text',
                ],
            ]),
        ]);

        $service = app(WhatsAppGatewayService::class);

        $this->assertTrue($service->sendMessage('917035624149', 'Hello', '7099481497'));
        $this->assertSame('provider-message-id', data_get($service->getLastResponseData(), 'id'));
    }

    public function test_it_detects_a_ready_configured_session(): void
    {
        Http::fake([
            'http://whatsapp.test/health' => Http::response([
                'success' => true,
                'data' => [
                    'sessions' => [
                        ['sessionKey' => '7099481497', 'status' => 'ready'],
                        ['sessionKey' => '7000000000', 'status' => 'qr_required'],
                    ],
                ],
            ]),
        ]);

        $service = app(WhatsAppGatewayService::class);

        $this->assertTrue($service->hasReadySession(['7099481497', '7000000000']));
        $this->assertNull($service->getLastError());
    }

    public function test_it_reuses_the_same_idempotency_key_for_a_reminder_retry(): void
    {
        Http::fake([
            'http://whatsapp.test/messages/send' => Http::response(['success' => true]),
        ]);

        $service = app(WhatsAppGatewayService::class);

        $this->assertTrue($service->sendMessage('917035624149', 'Reminder', '7099481497', null, 'follow-up:279'));
        $this->assertTrue($service->sendMessage('917035624149', 'Reminder', '7099481497', null, 'follow-up:279'));

        $keys = Http::recorded()
            ->map(fn ($record) => $record[0]['idempotencyKey'])
            ->all();

        $this->assertCount(2, $keys);
        $this->assertSame($keys[0], $keys[1]);
    }
}
