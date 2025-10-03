<?php namespace App\Models;

use CodeIgniter\Model;

class M_stock extends Model {
    protected $table = 'medicine_stock';
    protected $allowedFields = ['medicine_id', 'quantity', 'created_at','status'];

public function insert_stock($data)
  {
      $this->insertBatch($data);
      return $this->insertID();
  }
public function getStockBalance($medicineId)
 {
    $result = $this->select('SUM(quantity) as currentStock')
                   ->where('medicine_id', $medicineId)
                   ->get()
                   ->getRow();

    return $result ? (int)$result->currentStock : 0;
 }
public function get_stocks()
{
    return $this->select('mm.name, SUM(medicine_stock.quantity) as currentStock,medicine_id')
        ->join('medicine_master mm', 'mm.id = medicine_stock.medicine_id')
        ->groupBy('medicine_stock.medicine_id')
        ->get()
        ->getResult();
}
public function get_stock_history($medicineId = null)
{
    $builder = $this->db->table('medicine_stock m');
    $builder->select("
        DATE(m.created_at) AS date,
        SUM(CASE WHEN m.quantity > 0 THEN m.quantity ELSE 0 END) AS total_purchase,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) ELSE 0 END) AS total_sale
    ", false);

    if ($medicineId) {
        $builder->where('m.medicine_id', $medicineId);
    }

    $builder->groupBy("DATE(m.created_at)");
    $builder->orderBy("date", "ASC");

    $rows = $builder->get()->getResult();

    // Calculate cumulative balance manually
    $balance = 0;
    foreach ($rows as &$row) {
        $balance += ($row->total_purchase - $row->total_sale);
        $row->net_balance = $balance;
    }

    return $rows;
}

public function get_all_medicines_monthly_history()
{
    return $this->select("
            mm.name as medicine_name,
            DATE_FORMAT(medicine_stock.created_at, '%Y-%m') as month,
            SUM(CASE WHEN medicine_stock.quantity > 0 THEN medicine_stock.quantity ELSE 0 END) as total_purchase,
            SUM(CASE WHEN medicine_stock.quantity < 0 THEN ABS(medicine_stock.quantity) ELSE 0 END) as total_sale
        ", false)
        ->join('medicine_master mm', 'mm.id = medicine_stock.medicine_id')
        ->groupBy(['mm.name', "DATE_FORMAT(medicine_stock.created_at, '%Y-%m')"])
        ->orderBy('month', 'ASC')
        ->get()
        ->getResultArray();
}

}
?>
