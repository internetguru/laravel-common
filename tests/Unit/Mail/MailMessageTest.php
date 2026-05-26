<?php

namespace Tests\Unit\Mail;

use InternetGuru\LaravelCommon\Mail\MailMessage;
use Tests\TestCase;

class MailMessageTest extends TestCase
{
    public function test_subject_appends_ref_number()
    {
        $message = new MailMessage();
        $message->subject('Hello');

        $this->assertMatchesRegularExpression('/^Hello \(Ref [A-Z0-9]{5}\)$/', $message->subject);
    }

    public function test_without_ref_number_suppresses_ref_from_subject()
    {
        $message = new MailMessage();
        $message->withoutRefNumber()->subject('Hello');

        $this->assertEquals('Hello', $message->subject);
    }

    public function test_set_ref_number_overrides_auto_generated_ref()
    {
        $message = new MailMessage();
        $message->setRefNumber('abc12')->subject('Hello');

        $this->assertEquals('Hello (Ref ABC12)', $message->subject);
    }

    public function test_to_single_address_without_name()
    {
        $message = new MailMessage();
        $message->to('user@example.com');

        $this->assertEquals([['user@example.com', null]], $message->to);
    }

    public function test_to_single_address_with_name()
    {
        $message = new MailMessage();
        $message->to('user@example.com', 'User Name');

        $this->assertEquals([['user@example.com', 'User Name']], $message->to);
    }

    public function test_to_named_array()
    {
        $message = new MailMessage();
        $message->to(['user@example.com' => 'User', 'admin@example.com' => 'Admin']);

        $this->assertEquals([
            ['user@example.com', 'User'],
            ['admin@example.com', 'Admin'],
        ], $message->to);
    }

    public function test_to_indexed_array()
    {
        $message = new MailMessage();
        $message->to(['user@example.com', 'admin@example.com']);

        $this->assertEquals([
            ['user@example.com', null],
            ['admin@example.com', null],
        ], $message->to);
    }

    public function test_to_can_be_called_multiple_times()
    {
        $message = new MailMessage();
        $message->to('a@example.com')->to('b@example.com');

        $this->assertCount(2, $message->to);
        $this->assertEquals('a@example.com', $message->to[0][0]);
        $this->assertEquals('b@example.com', $message->to[1][0]);
    }

    public function test_data_sets_noreply_message_when_no_from_set()
    {
        $message = new MailMessage();
        $data = $message->data();

        $this->assertArrayHasKey('noreplyMessage', $data);
        $this->assertNotEmpty($data['noreplyMessage']);
    }

    public function test_data_suppresses_noreply_message_when_reply_to_is_set()
    {
        $message = new MailMessage();
        $message->replyTo('support@example.com');
        $data = $message->data();

        $this->assertEquals('', $data['noreplyMessage']);
    }

    public function test_data_noreply_message_present_for_noreply_from_address()
    {
        $message = new MailMessage();
        $message->from = [['no-reply@example.com', null]];
        $data = $message->data();

        $this->assertNotEmpty($data['noreplyMessage']);
    }

    public function test_data_suppresses_noreply_message_for_valid_from_address()
    {
        $message = new MailMessage();
        $message->from = [['support@example.com', null]];
        $data = $message->data();

        $this->assertEquals('', $data['noreplyMessage']);
    }

    public function test_without_ref_number_is_chainable()
    {
        $message = new MailMessage();
        $result = $message->withoutRefNumber();

        $this->assertInstanceOf(MailMessage::class, $result);
    }

    public function test_set_ref_number_is_chainable()
    {
        $message = new MailMessage();
        $result = $message->setRefNumber('abc12');

        $this->assertInstanceOf(MailMessage::class, $result);
    }
}
