<?php
// Chat giữa khách và nhân viên
class Chat
{
    const MAX_LEN = 1000;

    public static function latestByToken(string $token): array|false
    {
        $st = Database::get()->prepare('SELECT * FROM chat_conversations WHERE guest_token = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$token]);
        return $st->fetch();
    }

    public static function find(int $id): array|false
    {
        $st = Database::get()->prepare(
            'SELECT c.*, b.name AS branch_name, u.full_name AS assigned_name
             FROM chat_conversations c
             LEFT JOIN branches b ON b.id = c.branch_id
             LEFT JOIN users u ON u.id = c.assigned_user_id
             WHERE c.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public static function create(string $token, string $name, int $branchId): int
    {
        $pdo = Database::get();
        $pdo->prepare("INSERT INTO chat_conversations (guest_name, guest_token, branch_id, status, last_message_at) VALUES (?, ?, ?, 'open', NOW())")
            ->execute([$name, $token, $branchId]);
        return (int)$pdo->lastInsertId();
    }

    public static function addMessage(int $convId, string $type, ?int $userId, string $text): int
    {
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message) VALUES (?, ?, ?, ?)')
            ->execute([$convId, $type, $userId, $text]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?')->execute([$convId]);
        return $id;
    }

    // Tin sau $afterId. Lần đầu (afterId = 0) lấy 200 tin mới nhất
    public static function messages(int $convId, int $afterId): array
    {
        $pdo = Database::get();
        $base = 'SELECT m.id, m.sender_type, m.message, m.created_at, u.full_name AS staff_name
                 FROM chat_messages m LEFT JOIN users u ON u.id = m.sender_user_id WHERE m.conversation_id = ?';
        if ($afterId > 0) {
            $st = $pdo->prepare($base . ' AND m.id > ? ORDER BY m.id LIMIT 300');
            $st->execute([$convId, $afterId]);
        } else {
            $st = $pdo->prepare('SELECT * FROM (' . $base . ' ORDER BY m.id DESC LIMIT 200) t ORDER BY id');
            $st->execute([$convId]);
        }
        return $st->fetchAll();
    }

    // Chuyển dòng DB thành dữ liệu gửi cho trình duyệt. $viewer: customer hoặc staff
    public static function present(array $rows, string $viewer): array
    {
        $out = [];
        foreach ($rows as $r) {
            $ts = strtotime($r['created_at']);
            $m = [
                'id'   => (int)$r['id'],
                'from' => $viewer === 'customer' ? ($r['sender_type'] === 'customer' ? 'me' : 'staff') : $r['sender_type'],
                'text' => $r['message'],
                'time' => date('Y-m-d', $ts) === date('Y-m-d') ? date('H:i', $ts) : date('d/m H:i', $ts),
            ];
            if ($viewer === 'staff' && $r['sender_type'] === 'staff') $m['name'] = $r['staff_name'];
            $out[] = $m;
        }
        return $out;
    }

    // $reader = customer: khách đã đọc tin của nhân viên. $reader = staff: nhân viên đã đọc tin của khách
    public static function markRead(int $convId, string $reader): void
    {
        $from = $reader === 'customer' ? 'staff' : 'customer';
        Database::get()->prepare('UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = ? AND is_read = 0')
            ->execute([$convId, $from]);
    }

    public static function unreadForCustomer(int $convId): int
    {
        $st = Database::get()->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_type = 'staff' AND is_read = 0");
        $st->execute([$convId]);
        return (int)$st->fetchColumn();
    }

    // Số tin của khách chưa được nhân viên đọc, trong các cuộc chat đang mở
    public static function unreadCount(?int $branchId): int
    {
        $sql = "SELECT COUNT(*) FROM chat_messages m JOIN chat_conversations c ON c.id = m.conversation_id
                WHERE m.sender_type = 'customer' AND m.is_read = 0 AND c.status = 'open'";
        $p = [];
        if ($branchId) { $sql .= ' AND c.branch_id = ?'; $p[] = $branchId; }
        $st = Database::get()->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    // Số tin khách đã gửi trong $seconds giây gần đây (chống spam)
    public static function customerMsgCount(int $convId, int $seconds): int
    {
        $st = Database::get()->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_type = 'customer' AND created_at > (NOW() - INTERVAL " . (int)$seconds . " SECOND)");
        $st->execute([$convId]);
        return (int)$st->fetchColumn();
    }

    public static function inbox(?int $branchId, string $status): array
    {
        $sql = "SELECT c.*, b.name AS branch_name, u.full_name AS assigned_name,
                       (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id AND m.sender_type = 'customer' AND m.is_read = 0) AS unread,
                       (SELECT m2.message FROM chat_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.id DESC LIMIT 1) AS last_msg,
                       (SELECT m3.sender_type FROM chat_messages m3 WHERE m3.conversation_id = c.id ORDER BY m3.id DESC LIMIT 1) AS last_from,
                       TIMESTAMPDIFF(MINUTE, c.last_message_at, NOW()) AS idle_min
                FROM chat_conversations c
                LEFT JOIN branches b ON b.id = c.branch_id
                LEFT JOIN users u ON u.id = c.assigned_user_id
                WHERE c.status = ?";
        $p = [$status === 'closed' ? 'closed' : 'open'];
        if ($branchId) { $sql .= ' AND c.branch_id = ?'; $p[] = $branchId; }
        $st = Database::get()->prepare($sql . ' ORDER BY unread DESC, c.last_message_at DESC LIMIT 200');
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function assign(int $id, int $userId): void
    {
        Database::get()->prepare('UPDATE chat_conversations SET assigned_user_id = ? WHERE id = ?')->execute([$userId, $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::get()->prepare('UPDATE chat_conversations SET status = ? WHERE id = ?')->execute([$status === 'closed' ? 'closed' : 'open', $id]);
    }
}
