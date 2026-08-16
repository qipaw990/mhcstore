<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs px-3 py-2">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark"></i></a>
        <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">Chat & Notifikasi</h6>
    </div>
    <span class="badge text-white px-2.5 py-1 rounded-pill" style="background:#EE2737; font-size: 10px;">
        <?= count($notifications) ?> Pesan
    </span>
</div>

<div class="px-3 py-2.5">
    <!-- Chat with CS / Driver Quick Card -->
    <div class="p-3 bg-white border shadow-xs mb-2.5 d-flex align-items-center justify-content-between" style="border-radius: 14px;">
        <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: #EE2737;">
                <i class="bi bi-headset" style="font-size: 16px;"></i>
            </div>
            <div>
                <div class="fw-bold text-dark" style="font-size: 11.5px;">Customer Care CicalengkaGO</div>
                <div class="text-muted" style="font-size: 9.5px;">Bantuan pesanan & kurir 24/7</div>
            </div>
        </div>
        <a href="javascript:void(0)" onclick="Swal.fire({title:'Pusat Bantuan', text:'Tim Customer Care CicalengkaGO siap melayani bantuan pesanan Anda 24 jam.', icon:'info', confirmButtonColor:'#EE2737'})" class="btn btn-sm rounded-pill fw-bold px-3 py-1 text-white" style="background:#EE2737; font-size: 10px;">
            <i class="bi bi-headset me-0.5"></i> Bantuan
        </a>
    </div>

    <!-- Active Chats with Courier -->
    <?php if (!empty($active_chats)): ?>
        <div class="mb-3">
            <h6 class="fw-bold mb-2 text-dark" style="font-size: 12px;"><i class="bi bi-chat-dots-fill text-danger me-1"></i> Percakapan Driver</h6>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($active_chats as $chat): ?>
                    <a href="<?= $baseUrl ?>/orders/<?= htmlspecialchars($chat['order_code']) ?>/tracking?open_chat=1" class="p-3 bg-white border shadow-xs d-flex align-items-center justify-content-between text-decoration-none text-dark position-relative hover-shadow transition" style="border-radius: 14px;">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="position-relative">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($chat['dm_avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle border border-2 border-danger" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php if (!empty($chat['unread_chat_count']) && $chat['unread_chat_count'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 8.5px;">
                                        <?= $chat['unread_chat_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 11.5px;"><?= htmlspecialchars($chat['dm_name'] ?? 'Mitra Driver') ?></div>
                                <div class="text-muted text-truncate" style="font-size: 10px; max-width: 170px;">
                                    <?= !empty($chat['last_message']) ? htmlspecialchars($chat['last_message']) : 'Pesanan #' . htmlspecialchars($chat['order_code']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-1">
                            <span class="badge bg-danger text-white rounded-pill px-2.5 py-1" style="font-size: 9px;">
                                <i class="bi bi-chat-fill me-0.5"></i> Chat
                            </span>
                            <?php if (!empty($chat['last_chat_time'])): ?>
                                <span class="text-muted" style="font-size: 9px;"><?= date('H:i', strtotime($chat['last_chat_time'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <h6 class="fw-bold mb-2 text-dark" style="font-size: 12px;"><i class="bi bi-bell-fill text-warning me-1"></i> Pesan & Notifikasi</h6>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted bg-light border p-3" style="border-radius: 14px;">
            <i class="bi bi-chat-dots-fill display-6 text-muted mb-2 d-block"></i>
            <h6 class="fw-bold" style="color: var(--gojek-charcoal); font-size: 12px;">Belum Ada Pesan Masuk</h6>
            <p class="text-muted mb-0" style="font-size: 10px;">Pemberitahuan status pesanan dan promo akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($notifications as $n): ?>
                <div class="p-3 bg-white border shadow-xs d-flex align-items-start gap-2.5" style="border-radius: 14px;">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background: #EE2737;">
                        <i class="bi bi-bell-fill" style="font-size: 14px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 11px;"><?= htmlspecialchars($n['title']) ?></div>
                        <p class="text-muted mb-1" style="font-size: 10px; line-height: 1.35;"><?= htmlspecialchars($n['message']) ?></p>
                        <span class="text-muted" style="font-size: 9px;"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
