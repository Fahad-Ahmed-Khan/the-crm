<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Lead_manager_meeting_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name'      => 'Topic',
                'key'       => '{topic}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Meeting ID',
                'key'       => '{meeting_id}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Meeting Time',
                'key'       => '{meeting_time}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Duration',
                'key'       => '{meeting_duration}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Password',
                'key'       => '{meeting_password}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Join Link',
                'key'       => '{join_url}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Description',
                'key'       => '{meeting_description}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'Created at',
                'key'       => '{created_at}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead',
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'User name',
                'key'       => '{name}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-staff'
                ],
            ],
            [
                'name'      => 'User email',
                'key'       => '{email}',
                'available' => ['lead_manager_meeting'],
                'templates' => [
                    'lead-manager-send-to-staff'
                ],
            ]
        ];
    }

    /**
     * Lead manager meeting merge fields
     * @param  mixed $id meeting id
     * @return array
     */
    public function format($meeting_data)
    {
        $fields = [];
        $fields['{topic}'] = '';
        $fields['{meeting_id}'] = '';
        $fields['{meeting_time}'] = '';
        $fields['{meeting_duration}'] = '';
        $fields['{meeting_password}'] = '';
        $fields['{join_url}'] = '';
        $fields['{meeting_description}'] = '';
        $fields['{created_at}'] = '';
        $dateTime = new DateTime();
        $dateTime->setTimeZone(new DateTimeZone($meeting_data->timezone));
        $time_zone_abbreviation = $dateTime->format('T');
        $fields['{topic}'] = $meeting_data->meeting_agenda;
        $fields['{meeting_id}'] = $meeting_data->meeting_id;
        $fields['{meeting_time}'] = _dt($meeting_data->meeting_date) . ' ' . $time_zone_abbreviation;
        $fields['{meeting_duration}'] = $meeting_data->meeting_duration;
        $fields['{meeting_password}'] = $meeting_data->password;
        $fields['{join_url}'] = $meeting_data->join_url;
        $fields['{meeting_description}'] = $meeting_data->meeting_description;
        $fields['{created_at}'] = _dt($meeting_data->created_at);
        $fields['{name}'] = $meeting_data->name;
        $fields['{email}'] = $meeting_data->email;
        return $fields;
    }
}
