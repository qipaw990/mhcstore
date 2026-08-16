<div class="p-2 border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs" style="padding: 8px 12px !important;">
    <div class="d-flex align-items-center gap-1.5">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;"><i class="bi bi-arrow-left"></i></a>
        <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal); font-size: 12px;">Chat & Notifikasi</h6>
    </div>
    <span class="badge text-white px-2 py-0.5 rounded-pill" style="background:#EE2737; font-size: 9px;">
        <?= count($notifications) ?> Pesan
    </span>
</div>

<div class="p-2" style="padding: 8px 10px !important;">
    <!-- Chat with CS / Driver Quick Card -->
    <div class="p-2 bg-white rounded-3 border shadow-xs mb-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background: #EE2737;">
                <i class="bi bi-headset" style="font-size: 14px;"></i>
            </div>
            <div>
                <div class="fw-bold text-dark" style="font-size: 10.5px;">Customer Care CicalengkaGO</div>
                <div class="text-muted" style="font-size: 9px;">Bantuan pesanan & kurir 24/7</div>
            </div>
        </div>
        <a href="javascript:void(0)" onclick="Swal.fire({title:'Pusat Bantuan', text:'Tim Customer Care CicalengkaGO siap melayani bantuan pesanan Anda 24 jam.', icon:'info', confirmButtonColor:'#EE2737'})" class="btn btn-sm rounded-pill fw-bold px-2.5 py-1 text-white" style="background:#EE2737; font-size: 9.5px;">
            <i class="bi bi-headset me-0.5"></i> Bantuan
        </a>
    </div>

    <!-- Active Chats with Courier -->
    <?php if (!empty($active_chats)): ?>
        <div class="mb-2.5">
            <h6 class="fw-bold mb-1.5" style="font-size: 11px; color: var(--gojek-charcoal);"><i class="bi bi-chat-dots-fill text-danger me-1"></i> Percakapan Driver</h6>
            <div class="d-flex flex-column gap-1.5">
                <?php foreach ($active_chats as $chat): ?>
                    <a href="<?= $baseUrl ?>/orders/<?= htmlspecialchars($chat['order_code']) ?>/tracking?open_chat=1" class="p-2 bg-white rounded-3 border shadow-xs d-flex align-items-center justify-content-between text-decoration-none text-dark position-relative hover-shadow transition">
                        <div class="d-flex align-items-center gap-2">
                            <div class="position-relative">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($chat['dm_avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle border border-2 border-danger" style="width: 34px; height: 34px; object-fit: cover;">
                                <?php if (!empty($chat['unread_chat_count']) && $chat['unread_chat_count'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 8px;">
                                        <?= $chat['unread_chat_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 10.5px;"><?= htmlspecialchars($chat['dm_name'] ?? 'Mitra Driver') ?></div>
                                <div class="text-muted text-truncate" style="font-size: 9.5px; max-width: 170px;">
                                    <?= !empty($chat['last_message']) ? htmlspecialchars($chat['last_message']) : 'Pesanan #' . htmlspecialchars($chat['order_code']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-0.5">
                            <span class="badge bg-danger text-white rounded-pill px-2 py-0.5" style="font-size: 8.5px;">
                                <i class="bi bi-chat-fill me-0.5"></i> Chat
                            </span>
                            <?php if (!empty($chat['last_chat_time'])): ?>
                                <span class="text-muted" style="font-size: 8.5px;"><?= date('H:i', strtotime($chat['last_chat_time'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <h6 class="fw-bold mb-1.5" style="font-size: 11px; color: var(--gojek-charcoal);"><i class="bi bi-bell-fill text-warning me-1"></i> Pesan & Notifikasi</h6>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-4 text-muted bg-light rounded-3 border p-2">
            <i class="bi bi-chat-dots-fill display-6 text-muted mb-1 d-block"></i>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal); font-size: 11px;">Belum Ada Pesan Masuk</h6>
            <p class="text-muted mb-0" style="font-size: 9.5px;">Pemberitahuan status pesanan dan promo akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-1.5">
            <?php foreach ($notifications as $n): ?>
                <div class="p-2 bg-white rounded-3 border shadow-xs d-flex align-items-start gap-2">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background: #EE2737;">
                        <i class="bi bi-bell-fill" style="font-size: 12px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 10px;"><?= htmlspecialchars($n['title']) ?></div>
                        <p class="text-muted mb-0.5" style="font-size: 9.5px; line-height: 1.3;"><?= htmlspecialchars($n['message']) ?></p>
                        <span class="text-muted" style="font-size: 8.5px;"><i class="bi bi-clock me-0.5"></i><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
