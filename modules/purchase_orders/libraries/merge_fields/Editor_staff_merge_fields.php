<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Editor_staff_merge_fields extends App_merge_fields
{
    public function build()
    {

        $available = ['purchase_order'];

        return [
            [
                'name'      => 'Editor (Staff) Firstname',
                'key'       => '{editor_staff_firstname}',
                'available' => $available,
            ],
            [
                'name'      => 'Editor (Staff) Lastname',
                'key'       => '{editor_staff_lastname}',
                'available' => $available,
            ],
            [
                'name'      => 'Editor (Staff) Email',
                'key'       => '{editor_staff_email}',
                'available' => $available,
            ],
            [
                'name'      => 'Editor (Staff) Date Created',
                'key'       => '{editor_staff_datecreated}',
                'available' => $available,
            ],
        ];
    }

    /**
     * Merge field for staff members
     * @param  mixed $editor_staff_id staff id
     * @param  string $password password is used only when sending welcome email, 1 time
     * @return array
     */
    public function format($editor_staff_id)
    {
        $fields = [];

        $this->ci->db->where('staffid', $editor_staff_id);
        $staff = $this->ci->db->get(db_prefix() . 'staff')->row();

        $fields['{editor_staff_firstname}']   = '';
        $fields['{editor_staff_lastname}']    = '';
        $fields['{editor_staff_email}']       = '';
        $fields['{editor_staff_datecreated}'] = '';

        if (!$staff) {
            return $fields;
        }

        $fields['{editor_staff_firstname}']   = $staff->firstname;
        $fields['{editor_staff_lastname}']    = $staff->lastname;
        $fields['{editor_staff_email}']       = $staff->email;
        $fields['{editor_staff_datecreated}'] = $staff->datecreated;


        $custom_fields = get_custom_fields('staff');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($editor_staff_id, $field['id'], 'staff');
        }

        return hooks()->apply_filters('editor_staff_merge_fields', $fields, [
            'id'    => $editor_staff_id,
            'staff' => $staff,
        ]);
    }
}
