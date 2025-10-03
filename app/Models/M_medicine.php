<?php

namespace App\Models;

use CodeIgniter\Model;

class M_medicine extends Model
{
    protected $table = 'medicine_master';

    protected $allowedFields = [
        'id',
        'name',
        'price',
        'status',
        'created_at',
    ];
public function insert_medicine($data)
  {
      $this->insert($data);
      return $this->insertID();
  }
public function getMedicines()
  {
    $this->where('status',1);
    return $this->get()->getResult();
  }
public function getMedicine($medicineid)
  {
    $this->where('id',$medicineid);
    return $this->get()->getRow();
  }
public function edit_medicine($medicinedata,$medicineid)
  {
    $this->set($medicinedata);
    $this->where('id',$medicineid);
    $res = $this->update();
    return $res?true:false;
  }
public function medicine_remove($medicineid)
  {
    $this->where('id',$medicineid);
    $this->set('status',0);
    $resp= $this->update();
    return $resp?true:false;
  }
}
?>
