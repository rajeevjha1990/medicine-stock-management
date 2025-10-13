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
public function change_password_form()
  {
    if (!session()->get('logged_in')) {
        return redirect()->to('/');
    }
    $data['admin_name'] = $this->session->get('admin_name');
    echo view('includes/header',$data);
    echo view('includes/sidebar');
    echo view('change_password');
    echo view('includes/footer');
  }
public function change_password()
{
    $m_admin = new \App\Models\M_admin();
    $this->validation->setRule('current_password', 'Current Password', 'required');
    $this->validation->setRule('new_password', 'New Password', 'required');
    $this->validation->setRule('confirm_password', 'Confirm Password', 'required');

    if (!$this->validation->withRequest($this->request)->run()) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => false,
            'error' => $this->validation->getErrors()
        ]);
    }

    if ($this->request->getVar('new_password') != $this->request->getVar('confirm_password')) {
      return $this->response->setJSON([
          'status' => false,
          'err' => "New password and confirm password do not match."
      ]);
    }

    $adminid = $this->session->get('id');
    $old_password = $this->request->getVar('current_password');
    $new_password = $this->request->getVar('new_password');

    $admindata = $m_admin->getAdminData($adminid);
    $hashed_pwd = $admindata->admin_password ?? '';

    if (password_verify($old_password, $hashed_pwd)) {
        $update = $m_admin->new_password_set($adminid, password_hash($new_password, PASSWORD_DEFAULT));

        if ($update) {
            $this->session->destroy();
            return $this->response->setJSON([
                'status' => true,
                'message' => "Password reset successfully.",
                'reload'=> redirect()->to('/')
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => false,
                'error' => "Failed to update password."
            ]);
        }
      } else {
          return $this->response->setStatusCode(405)->setJSON([
              'status' => false,
              'error' => "Old password is incorrect."
          ]);
      }
  }
}
?>
