<?php
namespace App\Controllers;

class Medicine extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->validation = \Config\Services::validation();

    }
  public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/');
        }
        $m_medicine = new \App\Models\M_medicine();
        $data['admin_name'] = $this->session->get('admin_name');
        $data['medicines']=$m_medicine->getMedicines();
        echo view('includes/header',$data);
        echo view('includes/sidebar');
        echo view('medicine_list',$data);
        echo view('includes/footer');
    }
public function medicine_form()
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $data['admin_name'] = $this->session->get('admin_name');
    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('medicine_form');
    echo view('includes/footer');
  }
public function medicine_edit_form($medicineid)
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $m_medicine = new \App\Models\M_medicine();
    $data['admin_name'] = $this->session->get('admin_name');
    $data['medicine']=$m_medicine->getMedicine($medicineid);
    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('medicine_form');
    echo view('includes/footer');
  }
public function new_medicine()
{
    // Validation rules
    $this->validation->setRule("name", "Name", "required");
    $this->validation->setRule("price", "Price", "required|numeric");

    if (!$this->validation->withRequest($this->request)->run()) {
        // Validation fail -> send error JSON
        return $this->response->setStatusCode(422)->setJSON([
            'error' => $this->validation->getErrors()
        ]);
    }

    // Values
    $quantity = (int) $this->request->getVar('quantity');
    $price    = (int) $this->request->getVar('price');

    $medicinedata = [
        'name'          => $this->request->getVar('name'),
        'price'=> $this->request->getVar('price'),
    ];

    $m_medicine = new \App\Models\M_medicine();
    if ($this->request->getVar('id')) {
        $medicineid = $this->request->getVar('id');
        $resp = $m_medicine->edit_medicine($medicinedata, $medicineid);
    } else {
        $resp = $m_medicine->insert_medicine($medicinedata);
    }
    if ($resp) {
        return $this->response->setJSON([
            "status"  => true,
            "message" => "Medicine added successfully",
            "reload"  => 1
        ]);
    } else {
        return $this->response->setStatusCode(500)->setJSON([
            "status" => false,
            "message" => "Error, try again.."
        ]);
    }
}

public function remove_medicine($medicineid)
{
    $m_medicine = new \App\Models\M_medicine();

    $data = $m_medicine->medicine_remove($medicineid);

    // Response array
    $response = [];

    if ($data === false) {
        $response['success'] = false;
        $response['err']     = "Medicine not removed.";
    } else {
        $response['success'] = true;
        $response['message'] = "Medicine removed successfully.";
        $response['reload']  = 1;
    }
    // Set JSON response
    return $this->response
          ->setHeader('Content-Type', 'application/json')
          ->setBody(json_encode($response));
}
public function purchase_form()
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $m_medicine = new \App\Models\M_medicine();
    $data['admin_name'] = $this->session->get('admin_name');
    $data['medicines']=$m_medicine->getMedicines();
    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('purchase_form',$data);
    echo view('includes/footer');
  }
public function purchase_medicine()
{
    $medicines = $this->request->getVar('medicine');
    $quantities = $this->request->getVar('quantity');

    if (empty($medicines) || empty($quantities)) {
        return $this->response->setJSON([
            'status' => false,
            'message' => 'Please select medicine and enter quantity'
        ]);
    }
    $purchaseData = [];
    foreach ($medicines as $index => $medicineId) {
        if (empty($medicineId) || empty($quantities[$index])) {
            continue;
        }
        $purchaseData[] = [
            'medicine_id' => $medicineId,
            'quantity'    => (int)$quantities[$index],
        ];
    }
    $stockmodel = new \App\Models\M_stock();
    $stockmodel->insert_stock($purchaseData);
    return $this->response->setJSON([
        'status' => true,
        'message' => 'Medicine purchase saved successfully',
        'reload' => 1
    ]);
  }
public function medicine_stock()
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $stockmodel = new \App\Models\M_stock();
    $data['admin_name'] = $this->session->get('admin_name');
    $data['stocks']=$stockmodel->get_stocks();

    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('stock_list',$data);
    echo view('includes/footer');
  }
public function medicine_sale()
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $m_medicine = new \App\Models\M_medicine();
    $data['admin_name'] = $this->session->get('admin_name');
    $data['medicines']=$m_medicine->getMedicines();
    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('sale_form',$data);
    echo view('includes/footer');
  }
public function sale_medicine()
{
    $medicines  = $this->request->getVar('medicine');
    $quantities = $this->request->getVar('quantity');

    if (empty($medicines) || empty($quantities)) {
        return $this->response->setJSON([
            'status'  => false,
            'message' => 'Please select medicine and enter quantity',
            'reload'  => 0
        ]);
    }

    $stockmodel = new \App\Models\M_stock();
    $medicinemodel = new \App\Models\M_medicine();
    $saleData   = [];

    foreach ($medicines as $index => $medicineId) {
        if (empty($medicineId) || empty($quantities[$index])) {
            continue;
        }
        $saleQty      = (int)$quantities[$index];
        $currentStock = $stockmodel->getStockBalance($medicineId);
        $medicine = $medicinemodel->getMedicine($medicineId);

        if ($currentStock < $saleQty) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => "{$medicine->name} has only {$currentStock} in stock. You cannot sell {$saleQty}.",
                'reload'  => 0
            ]);
        }
        $saleData[] = [
            'medicine_id' => $medicineId,
            'quantity'    => -$saleQty,
            'type'        => 'sale',
            'created_at'  => date('Y-m-d H:i:s')
        ];
    }

    if (!empty($saleData)) {
        $stockmodel->insert_stock($saleData);
    }

    return $this->response->setJSON([
        'status'  => true,
        'message' => 'Medicine sale saved successfully',
        'reload'  => 1
    ]);
}
public function get_stock_history($medicineid)
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $stockmodel = new \App\Models\M_stock();
    $m_medicine = new \App\Models\M_medicine();
    $data['admin_name'] = $this->session->get('admin_name');
    $data['history']=$stockmodel->get_stock_history($medicineid);
    $data['medicine']=$m_medicine->getMedicine($medicineid);
    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('stock_history',$data);
    echo view('includes/footer');
  }

}
?>
