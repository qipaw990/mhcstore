<div class="border-bottom bg-white d-flex align-items-center justify-content-between sticky-top app-subpage-header" style="z-index: 1020; padding: 8px 10px !important; border-bottom-color: #E2E8F0 !important;">
    <div class="d-flex align-items-center gap-1.5 min-w-0">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; border: 1px solid #E2E8F0; background: #F8FAFC; padding: 0;">
            <i class="bi bi-arrow-left text-dark" style="font-size: 12px;"></i>
        </a>
        <h6 class="fw-bold m-0 text-dark text-truncate" style="font-size: 13px; letter-spacing: -0.2px;">Chat & Notifikasi</h6>
    </div>
    <span class="badge text-white rounded-pill flex-shrink-0" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 9px; padding: 3px 8px !important; font-weight: 600;">
        <?= count($notifications) ?> Pesan
    </span>
</div>

<div style="padding: 10px !important;">
    <!-- Chat with CS / Driver Quick Card -->
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px !important; padding: 9px 11px !important; margin-bottom: 10px !important; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03); display: flex; align-items: center; justify-content: space-between; gap: 8px !important;">
        <div style="display: flex; align-items: center; gap: 8px !important; min-width: 0;">
            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #EE2737, #C61524); color: #FFFFFF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 5px rgba(238, 39, 55, 0.25);">
                <i class="bi bi-headset" style="font-size: 14px;"></i>
            </div>
            <div style="min-width: 0;">
                <div style="font-size: 11px; font-weight: 700; color: #0F172A; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; letter-spacing: -0.2px;">Customer Care CicalengkaGO</div>
                <div style="font-size: 9px; color: #64748B; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">Bantuan pesanan & kurir 24/7</div>
            </div>
        </div>
        <a href="javascript:void(0)" onclick="Swal.fire({title:'Pusat Bantuan', text:'Tim Customer Care CicalengkaGO siap melayani bantuan pesanan Anda 24 jam.', icon:'info', confirmButtonColor:'#EE2737'})" class="btn btn-sm rounded-pill text-white flex-shrink-0" style="background: #EE2737; font-size: 9.5px !important; padding: 3px 10px !important; font-weight: 700; text-decoration: none;">
            <i class="bi bi-headset me-0.5" style="font-size: 9px;"></i> Bantuan
        </a>
    </div>

    <!-- Active Chats with Courier -->
    <?php if (!empty($active_chats)): ?>
        <div style="margin-bottom: 10px !important;">
            <h6 style="font-size: 11px; font-weight: 700; color: #0F172A; margin-top: 0 !important; margin-bottom: 8px !important; display: flex; align-items: center; gap: 4px;">
                <i class="bi bi-chat-dots-fill text-danger" style="font-size: 12px;"></i> Percakapan Driver
            </h6>
            <div style="display: flex; flex-direction: column; gap: 6px !important;">
                <?php foreach ($active_chats as $chat): ?>
                    <a href="<?= $baseUrl ?>/orders/<?= htmlspecialchars($chat['order_code']) ?>/tracking?open_chat=1" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px !important; padding: 8px 10px !important; display: flex; align-items: center; justify-content: space-between; gap: 8px !important; text-decoration: none; color: #0F172A; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);">
                        <div style="display: flex; align-items: center; gap: 8px !important; min-width: 0;">
                            <div class="position-relative flex-shrink-0">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($chat['dm_avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%; border: 2px solid #EE2737;">
                                <?php if (!empty($chat['unread_chat_count']) && $chat['unread_chat_count'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 7.5px; padding: 2px 4px;">
                                        <?= $chat['unread_chat_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 11px; font-weight: 700; color: #0F172A; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin-bottom: 1px;"><?= htmlspecialchars($chat['dm_name'] ?? 'Mitra Driver') ?></div>
                                <div style="font-size: 9px; color: #64748B; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                    <?= !empty($chat['last_message']) ? htmlspecialchars($chat['last_message']) : 'Pesanan #' . htmlspecialchars($chat['order_code']) ?>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px !important; flex-shrink: 0;">
                            <span style="font-size: 8.5px; padding: 2px 7px !important; border-radius: 20px !important; background: #EE2737; color: #FFFFFF; font-weight: 700; display: inline-flex; align-items: center; gap: 2px;">
                                <i class="bi bi-chat-fill" style="font-size: 8px;"></i> Chat
                            </span>
                            <?php if (!empty($chat['last_chat_time'])): ?>
                                <span style="font-size: 8px; color: #94A3B8;"><?= date('H:i', strtotime($chat['last_chat_time'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <h6 style="font-size: 11px; font-weight: 700; color: #0F172A; margin-top: 10px !important; margin-bottom: 8px !important; display: flex; align-items: center; gap: 4px;">
        <i class="bi bi-bell-fill text-warning" style="font-size: 12px;"></i> Pesan & Notifikasi
    </h6>

    <?php if (empty($notifications)): ?>
        <div style="text-align: center; padding: 20px 12px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px !important; color: #64748B;">
            <i class="bi bi-chat-dots-fill text-muted d-block mb-1.5" style="font-size: 24px;"></i>
            <h6 style="font-weight: 700; color: #0F172A; font-size: 11px; margin-bottom: 3px;">Belum Ada Pesan Masuk</h6>
            <p style="color: #64748B; font-size: 9.5px; margin: 0;">Pemberitahuan status pesanan dan promo akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 6px !important;">
            <?php foreach ($notifications as $n): ?>
                <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px !important; padding: 8px 10px !important; display: flex; align-items: flex-start; gap: 8px !important; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02); overflow: hidden;">
                    <div style="width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg, #EE2737, #C61524); color: #FFFFFF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 5px rgba(238, 39, 55, 0.2); font-size: 11px;">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div style="flex-grow: 1; min-width: 0;">
                        <div style="font-size: 11px; font-weight: 700; color: #0F172A; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin-bottom: 2px;"><?= htmlspecialchars($n['title']) ?></div>
                        <p style="font-size: 9px; color: #475569; line-height: 1.3; margin: 0 0 3px 0;"><?= htmlspecialchars($n['message']) ?></p>
                        <span style="font-size: 8px; color: #94A3B8; display: flex; align-items: center; gap: 2px;"><i class="bi bi-clock"></i><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
