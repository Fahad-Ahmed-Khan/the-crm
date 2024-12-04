<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Lead_manager_mailbox_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name'      => 'From Email',
                'key'       => '{from_email}',
                'available' => [
                    'lead_manager_email'
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-email-to-customer'
                ]
            ],
            [
                'name'      => 'From Name',
                'key'       => '{fromName}',
                'available' => [
                    'lead_manager_email'
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-email-to-customer'
                ]
            ],
            [
                'name'      => 'To',
                'key'       => '{to_email}',
                'available' => [
                    'lead_manager_email'
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-email-to-customer'
                ]
            ],
            [
                'name'      => 'CC',
                'key'       => '{to_cc}',
                'available' => [
                    'lead_manager_email'
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-email-to-customer'
                ]
            ],
            [
                'name'      => 'Subject',
                'key'       => '{subject}',
                'available' => [
                    'lead_manager_email'
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-email-to-customer'
                ]
            ],
            [
                'name'      => 'Message',
                'key'       => '{message}',
                'available' => [
                    'lead_manager_email'
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-email-to-customer'
                ]
            ],

        ];
    }

    /**
     * Lead manager mailbox merge fields
     * @param  mixed $id mailbox id
     * @return array
     */
    public function format($mailbox)
    {
        $fields = [];
        $fields['{from_email}'] = $mailbox['from_email'];
        $fields['{fromName}'] = $mailbox['fromName'];
        $fields['{to_email}'] = $mailbox['to_email'];
        $fields['{to_cc}'] = $mailbox['to_cc'];
        $fields['{subject}'] = $mailbox['subject'];
        $fields['{message}'] = $mailbox['message'];
        return $fields;
    }
}
