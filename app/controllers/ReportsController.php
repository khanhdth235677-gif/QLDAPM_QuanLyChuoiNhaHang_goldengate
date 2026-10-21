<?php
require_once APP_PATH . '/models/Branch.php';
require_once APP_PATH . '/models/Report.php';

// Báo cáo: admin xem toàn chuỗi hoặc từng chi nhánh, quản lý chỉ xem chi nhánh mình
class ReportsController extends Controller
{
    const METHODS = ['cash' => 'Tiền mặt', 'card' => 'Thẻ', 'transfer' => 'Chuyển khoản', 'ewallet' => 'Ví điện tử'];

    public function index(): void
    {
        $c = $this->common('reports', 'revenue');
        $days = (int)((strtotime($c['t']) - strtotime($c['f'])) / 86400) + 1;
        $by = ($_GET['by'] ?? '') === 'month' || $days > 92 ? 'month' : 'day';

        $this->view('reports/index', $c + [
            'title' => 'Báo cáo doanh thu', 'by' => $by, 'forcedMonth' => $days > 92,
            'sum' => Report::summary($c['bid'] ?: null, $c['f'], $c['t']),
            'periods' => Report::byPeriod($c['bid'] ?: null, $c['f'], $c['t'], $by),
            'methods' => Report::byMethod($c['bid'] ?: null, $c['f'], $c['t']), 'methodNames' => self::METHODS,
        ]);
    }

    public function items(): void
    {
        $c = $this->common('reports/items', 'items');
        $sort = ($_GET['sort'] ?? '') === 'revenue' ? 'revenue' : 'qty';
        $this->view('reports/items', $c + [
            'title' => 'Món bán chạy', 'sort' => $sort,
            'items' => Report::topItems($c['bid'] ?: null, $c['f'], $c['t'], $sort, 50),
            'cats'  => Report::byCategory($c['bid'] ?: null, $c['f'], $c['t']),
        ]);
    }

    public function hours(): void
    {
        $c = $this->common('reports/hours', 'hours');
        $this->view('reports/hours', $c + [
            'title' => 'Giờ và ngày cao điểm',
            'hourRows' => Report::byHour($c['bid'] ?: null, $c['f'], $c['t']),
            'dayRows'  => Report::byWeekday($c['bid'] ?: null, $c['f'], $c['t']),
        ]);
    }

    public function branches(): void
    {
        $c = $this->common('reports/branches', 'branches', true);
        $this->view('reports/branches', $c + ['title' => 'So sánh chi nhánh', 'rows' => Report::byBranch($c['f'], $c['t'])]);
    }

    public function usage(): void
    {
        $c = $this->common('reports/usage', 'usage');
        [$rows, $ok] = Report::usage($c['bid'] ?: null, $c['f'], $c['t']);
        $this->view('reports/usage', $c + ['title' => 'Báo cáo nguyên liệu', 'rows' => $rows, 'recipesOk' => $ok]);
    }

    // Xuất CSV mở được bằng Excel
    public function export(string $type = ''): void
    {
        $c = $this->common('reports', 'revenue', $type === 'branches');
        $b = $c['bid'] ?: null; $f = $c['f']; $t = $c['t'];
        $name = 'bao-cao-' . $type . '-' . $f . '-den-' . $t . '.csv';
        $rate = fn($v) => str_replace('.', ',', (string)round((float)$v, 2));   // dấu phẩy thập phân cho Excel tiếng Việt

        switch ($type) {
            case 'revenue':
                $rows = [];
                foreach (Report::byPeriod($b, $f, $t, (strtotime($t) - strtotime($f)) / 86400 + 1 > 92 || ($_GET['by'] ?? '') === 'month' ? 'month' : 'day') as $r) {
                    $rows[] = [$r['key'], $r['orders'], $r['subtotal'], $r['discount'], $r['vat'], $r['total']];
                }
                $this->csv($name, ['Kỳ', 'Số đơn', 'Tạm tính', 'Giảm giá', 'VAT', 'Doanh thu'], $rows);
            case 'items':
                $rows = [];
                foreach (Report::topItems($b, $f, $t, ($_GET['sort'] ?? '') === 'revenue' ? 'revenue' : 'qty', 1000) as $r) {
                    $rows[] = [$r['name'], $r['category_name'], (int)$r['qty'], (int)$r['revenue']];
                }
                $this->csv($name, ['Món', 'Danh mục', 'Số phần bán', 'Doanh thu theo giá niêm yết'], $rows);
            case 'hours':
                $rows = [];
                foreach (Report::byHour($b, $f, $t) as $r) $rows[] = [sprintf('%02d:00', $r['h']), $r['orders'], $r['revenue']];
                $this->csv($name, ['Giờ vào bàn', 'Số đơn', 'Doanh thu'], $rows);
            case 'branches':
                $rows = [];
                foreach (Report::byBranch($f, $t) as $r) $rows[] = [$r['name'], (int)$r['orders'], (int)$r['revenue'], (int)$r['discount'], (int)$r['vat']];
                $this->csv($name, ['Chi nhánh', 'Số đơn', 'Doanh thu', 'Giảm giá', 'VAT'], $rows);
            case 'usage':
                [$data] = Report::usage($b, $f, $t);
                $rows = [];
                foreach ($data as $r) $rows[] = [$r['name'], $r['unit'], $rate($r['theory']), $rate($r['auto_out']), $rate($r['manual_out']),
                                                 $rate($r['adjust_net']), $rate($r['loss']), $rate($r['qty_in'])];
                $this->csv($name, ['Nguyên liệu', 'Đơn vị', 'Dự kiến theo công thức', 'Đã tự trừ khi bán', 'Xuất tay', 'Kiểm kê điều chỉnh', 'Hao hụt ngoài bán hàng', 'Nhập kho'], $rows);
            default:
                http_response_code(404);
                exit('Loại báo cáo không hợp lệ.');
        }
    }

    // ---------- nội bộ ----------
    // Bộ lọc dùng chung: khoảng ngày, chi nhánh. Trả về các biến cho view
    private function common(string $action, string $tab, bool $adminOnly = false): array
    {
        if ($adminOnly) $this->requireRole('admin'); else $this->requireRole('admin', 'manager');
        $u = auth_user();
        $isAdmin = $u['role'] === 'admin';
        if (!$isAdmin && !$u['branch_id']) { flash('error', 'Tài khoản của bạn chưa được gán chi nhánh.'); redirect('dashboard'); }

        $t = $this->date($_GET['to'] ?? '', date('Y-m-d'));
        $f = $this->date($_GET['from'] ?? '', date('Y-m-01'));
        if ($f > $t) [$f, $t] = [$t, $f];
        if ((strtotime($t) - strtotime($f)) / 86400 > 365) $f = date('Y-m-d', strtotime($t . ' -365 days'));

        $branches = $isAdmin ? Branch::activeList() : [];
        $bid = $isAdmin ? (int)($_GET['branch'] ?? 0) : (int)$u['branch_id'];
        $branchName = $isAdmin ? 'Tất cả chi nhánh' : ($u['branch_name'] ?? '');
        if ($isAdmin && $bid) {
            $found = false;
            foreach ($branches as $b) if ((int)$b['id'] === $bid) { $found = true; $branchName = $b['name']; }
            if (!$found) $bid = 0;
        }

        $qs = http_build_query(array_filter(['from' => $f, 'to' => $t, 'branch' => $isAdmin && $bid ? $bid : null], fn($v) => $v !== null));
        return ['f' => $f, 't' => $t, 'bid' => $bid, 'branches' => $branches, 'isAdmin' => $isAdmin, 'qs' => $qs,
                'action' => $action, 'tab' => $tab, 'branchName' => $branchName];
    }

    private function date(string $raw, string $default): string
    {
        $raw = trim($raw);
        $d = DateTime::createFromFormat('Y-m-d', $raw);
        return ($d && $d->format('Y-m-d') === $raw) ? $raw : $default;
    }

    private function csv(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF" . "sep=;\r\n";            // BOM cho tiếng Việt, sep=; để Excel tách cột đúng
        $out = fopen('php://output', 'w');
        fputcsv($out, $header, ';', '"', '\\');
        foreach ($rows as $r) fputcsv($out, array_map([$this, 'cell'], $r), ';', '"', '\\');
        fclose($out);
        exit;
    }

    // Chống "CSV injection": ô văn bản bắt đầu bằng = + - @ sẽ bị Excel hiểu là công thức
    private function cell($v)
    {
        if (is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && !preg_match('/^-?\d+(,\d+)?$/', $v)) return "'" . $v;
        return $v;
    }
}
