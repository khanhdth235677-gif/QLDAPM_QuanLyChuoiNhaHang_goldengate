<?php
require_once APP_PATH . '/models/Category.php';
require_once APP_PATH . '/models/MenuItem.php';
require_once APP_PATH . '/models/Branch.php';
require_once ROOT_PATH . '/core/Upload.php';

// Thực đơn: admin quản lý món và danh mục; quản lý chi nhánh chỉ chỉnh giá và tình trạng bán tại chi nhánh mình
class MenuController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'manager');
        $cat = (int)($_GET['cat'] ?? 0);
        $this->view('menu/index', [
            'title' => 'Thực đơn', 'items' => MenuItem::list($cat ?: null), 'cats' => Category::all(), 'cat' => $cat,
        ]);
    }

    // ---------- Món ăn (admin) ----------
    public function create(): void
    {
        $this->requireRole('admin');
        $cats = Category::all();
        if (!$cats) {
            flash('error', 'Hãy tạo ít nhất một danh mục trước khi thêm món.');
            redirect('menu/categories');
        }
        $item = ['category_id' => 0, 'name' => '', 'description' => '', 'base_price' => '', 'status' => 1, 'image' => null];

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->readItem($cats);
            $item = array_merge($item, $_POST, ['status' => $data['status'] ?? 1]);
            if (!$err) {
                [$path, $err] = Upload::image($_FILES['image'] ?? null, 'menu');
                if (!$err) {
                    $data['image'] = $path;
                    MenuItem::create($data);
                    flash('success', 'Đã thêm món ' . $data['name'] . ' vào thực đơn của tất cả chi nhánh.');
                    redirect('menu');
                }
            }
            flash('error', $err);
        }
        $this->view('menu/form', ['title' => 'Thêm món', 'mode' => 'create', 'item' => $item, 'cats' => $cats]);
    }

    public function edit(string $id = ''): void
    {
        $this->requireRole('admin');
        $item = MenuItem::find((int)$id);
        if (!$item) $this->forbidden();
        $cats = Category::all();

        if ($this->isPost()) {
            csrf_check();
            [$data, $err] = $this->readItem($cats);
            $old  = $item['image'];
            $item = array_merge($item, $_POST, ['status' => $data['status'] ?? 1, 'image' => $old]);
            if (!$err) {
                [$path, $err] = Upload::image($_FILES['image'] ?? null, 'menu');
                if (!$err) {
                    $data['image'] = $old;
                    if ($path)                                  $data['image'] = $path;
                    elseif (!empty($_POST['remove_image']))     $data['image'] = null;

                    MenuItem::update((int)$id, $data, !empty($_POST['apply_all']));
                    if ($data['image'] !== $old) Upload::delete($old);
                    flash('success', 'Đã cập nhật món ' . $data['name'] . '.');
                    redirect('menu');
                }
            }
            flash('error', $err);
        }
        $this->view('menu/form', ['title' => 'Sửa món', 'mode' => 'edit', 'item' => $item, 'cats' => $cats]);
    }

    public function toggle(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('menu');
        $item = MenuItem::find((int)$id);
        if (!$item) $this->forbidden();

        $new = (int)$item['status'] === 1 ? 0 : 1;
        MenuItem::setStatus((int)$id, $new);
        flash('success', ($new ? 'Đã hiện lại món ' : 'Đã ẩn món ') . $item['name'] . '.');
        redirect('menu');
    }

    public function delete(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('menu');
        $item = MenuItem::find((int)$id);
        if (!$item) $this->forbidden();

        if (MenuItem::hasOrders((int)$id)) {
            flash('error', 'Món "' . $item['name'] . '" đã có trong hóa đơn nên không thể xóa. Hãy dùng nút Ẩn để ngừng bán.');
            redirect('menu');
        }
        MenuItem::delete((int)$id);
        Upload::delete($item['image']);
        flash('success', 'Đã xóa món ' . $item['name'] . '.');
        redirect('menu');
    }

    // ---------- Danh mục (admin) ----------
    public function categories(): void
    {
        $this->requireRole('admin');
        $this->view('menu/categories', ['title' => 'Danh mục món', 'cats' => Category::all()]);
    }

    public function savecategory(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('menu/categories');

        $name = trim($_POST['name'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        if ($name === '' || mb_strlen($name) > 100) { flash('error', 'Tên danh mục không hợp lệ (tối đa 100 ký tự).'); redirect('menu/categories'); }
        if ($sort < 0 || $sort > 999)               { flash('error', 'Thứ tự phải từ 0 đến 999.'); redirect('menu/categories'); }

        if ($id === '') {
            Category::create($name, $sort);
            flash('success', 'Đã thêm danh mục ' . $name . '.');
        } else {
            if (!Category::find((int)$id)) $this->forbidden();
            Category::update((int)$id, $name, $sort);
            flash('success', 'Đã cập nhật danh mục ' . $name . '.');
        }
        redirect('menu/categories');
    }

    public function deletecategory(string $id = ''): void
    {
        $this->requireRole('admin');
        $this->postOnly('menu/categories');
        $cat = Category::find((int)$id);
        if (!$cat) $this->forbidden();

        if ((int)$cat['item_count'] > 0) {
            flash('error', 'Danh mục "' . $cat['name'] . '" còn ' . $cat['item_count'] . ' món, hãy chuyển hoặc xóa các món trước.');
            redirect('menu/categories');
        }
        Category::delete((int)$id);
        flash('success', 'Đã xóa danh mục ' . $cat['name'] . '.');
        redirect('menu/categories');
    }

    // ---------- Giá và tình trạng bán theo chi nhánh (admin, quản lý) ----------
    public function branch(string $bid = ''): void
    {
        $this->requireRole('admin', 'manager');
        $u = auth_user();
        $branches = Branch::activeList();

        if ($u['role'] === 'manager') {
            if (!$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }
            $branchId = (int)$u['branch_id'];
        } else {
            $branchId = (int)$bid;
            if (!$branchId && $branches) $branchId = (int)$branches[0]['id'];
        }
        $branch = $branchId ? Branch::find($branchId) : false;
        if (!$branch) { flash('error', 'Chưa có chi nhánh nào.'); redirect('menu'); }

        $rows = MenuItem::branchPrices($branchId);

        if ($this->isPost()) {
            csrf_check();
            $save = [];
            foreach ($rows as $r) {
                $id  = (int)$r['id'];
                $raw = preg_replace('/\D/', '', (string)($_POST['price'][$id] ?? ''));
                if ($raw === '' || (int)$raw > 100000000) {
                    flash('error', 'Giá của món "' . $r['name'] . '" không hợp lệ.');
                    redirect('menu/branch/' . $branchId);
                }
                $save[$id] = ['price' => (int)$raw, 'avail' => isset($_POST['avail'][$id]) ? 1 : 0];
            }
            MenuItem::saveBranchPrices($branchId, $save);
            flash('success', 'Đã lưu giá và tình trạng bán tại ' . $branch['name'] . '.');
            redirect('menu/branch/' . $branchId);
        }

        $this->view('menu/branch', ['title' => 'Giá theo chi nhánh', 'rows' => $rows, 'branch' => $branch,
                                    'branches' => $branches, 'canPick' => $u['role'] === 'admin']);
    }

    private function readItem(array $cats): array
    {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $price = preg_replace('/\D/', '', (string)($_POST['base_price'] ?? ''));

        $okCat = false;
        foreach ($cats as $c) if ((int)$c['id'] === $catId) $okCat = true;

        if (!$okCat)                               return [null, 'Vui lòng chọn danh mục.'];
        if ($name === '' || mb_strlen($name) > 150) return [null, 'Tên món không hợp lệ (tối đa 150 ký tự).'];
        if (mb_strlen($desc) > 2000)               return [null, 'Mô tả quá dài (tối đa 2000 ký tự).'];
        if ($price === '' || (int)$price > 100000000) return [null, 'Giá không hợp lệ.'];

        return [[
            'category_id' => $catId, 'name' => $name, 'description' => $desc !== '' ? $desc : null,
            'base_price' => (int)$price, 'status' => isset($_POST['status']) ? 1 : 0, 'image' => null,
        ], null];
    }
}
