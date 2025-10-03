<?php

namespace App\Models;

use CodeIgniter\Model;

class M_admin extends Model
{
    protected $table = 'admin';

    protected $allowedFields = [
        'admin_id',
        'admin_name',
        'admin_mobile',
        'admin_email',
        'admin_password',
        'admin_status',
        'admin_created',
    ];
    public function adminlogin($username)
    {
        return $this->where('admin_email', $username)
                    ->orWhere('admin_mobile', $username)
                    ->get()->getRow();
    }

}
?>
