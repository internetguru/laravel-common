<?php

namespace Tests\Unit\Notifications;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use InternetGuru\LaravelCommon\Notifications\BaseNotification;
use InternetGuru\LaravelCommon\Services\GeolocationService;
use Tests\TestCase;
use Torann\GeoIP\Location;

class BaseNotificationTest extends TestCase
{
    private function notification(): BaseNotification
    {
        return new class extends BaseNotification {};
    }

    /** @return array<string, mixed> */
    private function mailData(BaseNotification $notification): array
    {
        return $notification->toMail(new AnonymousNotifiable)->viewData;
    }

    private function footer(?string $ip): string
    {
        return view('ig-common::components.emails.footer-html', [
            'ip' => $ip, 'timezone' => null, 'url' => null, 'noreplyMessage' => null, 'refNumber' => null, 'refLabel' => null,
        ])->render();
    }

    public function test_the_mail_names_the_requester_and_their_time_zone()
    {
        $this->mock(GeolocationService::class)->shouldReceive('getLocation')->andReturn(new Location(['timezone' => 'Europe/Prague']));

        $data = $this->mailData($this->notification());

        $this->assertSame(['127.0.0.1', 'Europe/Prague'], [$data['ip'], $data['timezone']]);
    }

    public function test_raising_a_notification_looks_up_no_location()
    {
        $this->mock(GeolocationService::class)->shouldNotReceive('getLocation');

        $this->assertSame('127.0.0.1', $this->notification()->ipAddress);
    }

    public function test_a_notification_on_someone_elses_behalf_names_no_requester()
    {
        $this->mock(GeolocationService::class)->shouldNotReceive('getLocation');

        $data = $this->mailData($this->notification()->withoutRequester());

        $this->assertSame([null, null, null], [$data['ip'], $data['timezone'], $data['userId']]);
    }

    public function test_the_footer_leaves_out_the_requester_line_without_one()
    {
        config(['filesystems.disks.root' => ['driver' => 'local', 'root' => base_path()]]);
        $line = Str::before(__('ig-common::layouts.email.requested-from'), ':ip');

        $this->assertStringContainsString($line, $this->footer('10.0.0.1'));
        $this->assertStringNotContainsString($line, $this->footer(null));
    }
}
