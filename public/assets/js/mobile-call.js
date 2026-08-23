/**
 * CicalengkaGO In-App Voice Call Engine (WebRTC + Web Audio API Signaling)
 * Enables real-time browser-to-browser voice calling between Customer and Driver
 */

(function () {
    'use strict';

    let peerConnection = null;
    let localStream = null;
    let currentCallId = null;
    let currentOrderCode = null;
    let currentCallOffer = null;
    let currentCallData = null;
    let processedCandidates = new Set();
    let pollInterval = null;
    let callTimerInterval = null;
    let callDurationSeconds = 0;
    let isCaller = false;
    let isMuted = false;
    let audioContext = null;
    let ringtoneTimer = null;

    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' },
            { urls: 'stun:stun3.l.google.com:19302' },
            { urls: 'stun:stun4.l.google.com:19302' },
            { urls: 'stun:global.stun.twilio.com:3478' }
        ],
        iceCandidatePoolSize: 10
    };

    // Inject Voice Call HTML Modal Container into DOM
    function injectCallUI() {
        if (document.getElementById('ccgVoiceCallModal')) return;

        const modalHtml = `
        <div id="ccgVoiceCallModal" class="ccg-call-overlay d-none">
            <div class="ccg-call-card shadow-2xl">
                <!-- Caller Avatar & Info -->
                <div class="ccg-call-user-section">
                    <div class="ccg-avatar-ring-container">
                        <div class="ccg-avatar-pulse"></div>
                        <img id="ccgCallAvatar" src="${window.BASE_URL || ''}/assets/images/users/driver.png" alt="Avatar" class="ccg-call-avatar">
                    </div>
                    <h5 id="ccgCallName" class="fw-bold text-white mb-1 mt-3 fs-5">Mitra Kurir</h5>
                    <div id="ccgCallSubtext" class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 fw-medium mb-2">CicalengkaGO Voice Call</div>
                    <div id="ccgCallTimer" class="ccg-call-timer font-monospace d-none">00:00</div>
                </div>

                <!-- Animated Sound Wave Visualizer -->
                <div id="ccgSoundVisualizer" class="ccg-sound-wave d-none">
                    <span></span><span></span><span></span><span></span><span></span>
                </div>

                <!-- Off-screen Remote Audio Player (Must NOT be display:none for mobile browser audio rendering!) -->
                <audio id="ccgRemoteAudio" autoplay playsinline style="position:fixed; top:-9999px; left:-9999px; width:1px; height:1px; opacity:0.01; pointer-events:none;"></audio>

                <!-- Incoming Call Actions (Answer / Reject) -->
                <div id="ccgIncomingActions" class="ccg-call-actions d-none">
                    <button type="button" onclick="window.CCGCall.rejectCall()" class="ccg-btn-call ccg-btn-reject" title="Tolak">
                        <i class="bi bi-telephone-x-fill"></i>
                        <span>Tolak</span>
                    </button>
                    <button type="button" onclick="window.CCGCall.answerCall()" class="ccg-btn-call ccg-btn-answer" title="Terima">
                        <i class="bi bi-telephone-fill"></i>
                        <span>Terima</span>
                    </button>
                </div>

                <!-- Active In-Call Actions (Mute / Speaker / End) -->
                <div id="ccgActiveActions" class="ccg-call-actions d-none">
                    <button type="button" id="ccgBtnMute" onclick="window.CCGCall.toggleMute()" class="ccg-btn-call ccg-btn-control" title="Mute Mic">
                        <i class="bi bi-mic-fill"></i>
                        <span id="ccgMuteLabel">Mute</span>
                    </button>
                    <button type="button" onclick="window.CCGCall.endCall()" class="ccg-btn-call ccg-btn-reject" title="Akhiri Panggilan">
                        <i class="bi bi-telephone-x-fill"></i>
                        <span>Akhiri</span>
                    </button>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        injectCallCSS();
    }

    // Permanently unlock audio element autoplay via dummy Web Audio stream on user touch gesture
    function unlockAudioElement() {
        const remoteAudio = document.getElementById('ccgRemoteAudio');
        if (!remoteAudio) return;

        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const dst = ctx.createMediaStreamDestination();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(440, ctx.currentTime);
            osc.connect(dst);
            osc.start(0);

            remoteAudio.srcObject = dst.stream;
            remoteAudio.muted = false;
            remoteAudio.volume = 1.0;

            const p = remoteAudio.play();
            if (p !== undefined) {
                p.then(() => {
                    console.log('[VoiceCall] Audio element unlocked via dummy stream!');
                }).catch(err => {
                    console.warn('[VoiceCall] Audio element unlock warning:', err);
                });
            }
        } catch (e) {
            console.warn('[VoiceCall] AudioContext unlock error:', e);
        }
    }

    // Attach and play remote audio stream
    function attachAndPlayRemoteStream(event) {
        const remoteAudio = document.getElementById('ccgRemoteAudio');
        if (!remoteAudio) return;

        let remoteStream = null;
        if (event.streams && event.streams[0]) {
            remoteStream = event.streams[0];
        } else if (event.track) {
            remoteStream = new MediaStream([event.track]);
        }

        if (remoteStream) {
            remoteStream.getAudioTracks().forEach(t => { t.enabled = true; });

            if (remoteAudio.srcObject !== remoteStream) {
                remoteAudio.srcObject = remoteStream;
            }

            remoteAudio.muted = false;
            remoteAudio.volume = 1.0;

            if (remoteAudio.paused) {
                remoteAudio.play().then(() => {
                    console.log('[VoiceCall] Remote audio playing smoothly.');
                }).catch(e => {
                    console.warn('[VoiceCall] Remote audio play error:', e);
                });
            }
        }
    }

    // Inject CSS styles for Voice Call UI
    function injectCallCSS() {
        if (document.getElementById('ccgVoiceCallStyles')) return;

        const css = `
        .ccg-call-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 999999;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: ccgFadeIn 0.3s ease-out;
        }

        .ccg-call-card {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            width: 100%;
            max-width: 360px;
            padding: 36px 24px;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .ccg-call-user-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ccg-avatar-ring-container {
            position: relative;
            width: 96px;
            height: 96px;
            margin: 0 auto;
        }

        .ccg-call-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #EE2737;
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 24px rgba(238, 39, 55, 0.3);
        }

        .ccg-avatar-pulse {
            position: absolute;
            top: -10px; left: -10px; right: -10px; bottom: -10px;
            border-radius: 50%;
            background: rgba(238, 39, 55, 0.35);
            animation: ccgPulseRing 1.8s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
            z-index: 1;
        }

        @keyframes ccgPulseRing {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.25); opacity: 0.3; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        .ccg-call-timer {
            font-size: 24px;
            font-weight: 700;
            color: #10B981;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        /* Sound Wave Animation */
        .ccg-sound-wave {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 28px;
            margin: 20px 0 10px;
        }

        .ccg-sound-wave span {
            display: block;
            width: 4px;
            height: 100%;
            background: #10B981;
            border-radius: 4px;
            animation: ccgWave 1.2s ease-in-out infinite alternate;
        }

        .ccg-sound-wave span:nth-child(1) { animation-delay: 0.1s; }
        .ccg-sound-wave span:nth-child(2) { animation-delay: 0.3s; }
        .ccg-sound-wave span:nth-child(3) { animation-delay: 0.5s; }
        .ccg-sound-wave span:nth-child(4) { animation-delay: 0.2s; }
        .ccg-sound-wave span:nth-child(5) { animation-delay: 0.4s; }

        @keyframes ccgWave {
            0% { height: 6px; opacity: 0.4; }
            100% { height: 28px; opacity: 1; }
        }

        /* Action Buttons */
        .ccg-call-actions {
            display: flex;
            align-items: center;
            justify-content: space-around;
            margin-top: 32px;
            gap: 16px;
        }

        .ccg-btn-call {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none;
            background: none;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ccg-btn-call i {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 8px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
            transition: transform 0.2s ease;
        }

        .ccg-btn-call:active i {
            transform: scale(0.92);
        }

        .ccg-btn-reject i {
            background: #EE2737;
            color: #ffffff;
        }

        .ccg-btn-answer i {
            background: #10B981;
            color: #ffffff;
            animation: ccgBounce 1s infinite alternate;
        }

        .ccg-btn-control i {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .ccg-btn-control.active i {
            background: #F59E0B;
            color: #ffffff;
        }

        @keyframes ccgBounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-6px); }
        }

        @keyframes ccgFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        `;

        const styleTag = document.createElement('style');
        styleTag.id = 'ccgVoiceCallStyles';
        styleTag.innerHTML = css;
        document.head.appendChild(styleTag);
    }

    let ringtoneAudio = null;
    let outgoingAudio = null;
    let isRingtoneActive = false;

    // Outgoing Dialing Tone for CALLER (Clean standard telephone "tuuut... tuuut..." dial tone)
    function playOutgoingTone() {
        stopRingtone();
        isRingtoneActive = true;

        try {
            const baseUrl = (window.BASE_URL && window.BASE_URL !== '') ? window.BASE_URL : window.location.origin;
            const dialtoneUrl = baseUrl + '/assets/audio/dialtone.wav?v=' + Date.now();
            outgoingAudio = new Audio(dialtoneUrl);
            outgoingAudio.loop = true;
            outgoingAudio.volume = 0.15; // Soft 15% volume
            const p = outgoingAudio.play();
            if (p !== undefined) {
                p.catch(() => {
                    playSynthDialTone();
                });
            }
        } catch (e) {
            playSynthDialTone();
        }

        // Safety fallback if HTML audio doesn't start playing within 350ms
        setTimeout(() => {
            if (isRingtoneActive && (!outgoingAudio || outgoingAudio.paused || outgoingAudio.currentTime === 0)) {
                playSynthDialTone();
            }
        }, 350);
    }

    function playSynthDialTone() {
        if (!isRingtoneActive || ringtoneTimer) return;
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            function ringPulse() {
                if (!audioContext || !isRingtoneActive) return;

                const osc = audioContext.createOscillator();
                const filter = audioContext.createBiquadFilter();
                const gain = audioContext.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(425, audioContext.currentTime);

                filter.type = 'lowpass';
                filter.frequency.setValueAtTime(550, audioContext.currentTime);

                const now = audioContext.currentTime;
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.linearRampToValueAtTime(0.008, now + 0.05);
                gain.gain.setValueAtTime(0.008, now + 0.95);
                gain.gain.linearRampToValueAtTime(0.0001, now + 1.0);

                osc.connect(filter);
                filter.connect(gain);
                gain.connect(audioContext.destination);

                osc.start(now);
                osc.stop(now + 1.0);
            }
            ringPulse();
            ringtoneTimer = setInterval(ringPulse, 3500);
        } catch (e) {}
    }

    // Incoming AI Voice Ringtone for RECEIVER (Very soft & gentle 15% volume)
    function playIncomingRingtone() {
        stopRingtone();
        isRingtoneActive = true;
        try {
            const ringtoneUrl = (window.BASE_URL || '') + '/assets/audio/ringtone.mp3?v=' + Date.now();
            ringtoneAudio = new Audio(ringtoneUrl);
            ringtoneAudio.loop = true;
            ringtoneAudio.volume = 0.15; // Very soft gentle volume (15%)
            const p = ringtoneAudio.play();
            if (p !== undefined) {
                p.catch(() => {
                    playVoiceSpeechRingtone();
                });
            }
        } catch (e) {
            playVoiceSpeechRingtone();
        }
    }

    function playVoiceSpeechRingtone() {
        if (!isRingtoneActive) return;
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.cancel();
                const utter = new SpeechSynthesisUtterance('Ada panggilan telepon masuk dari Cicalengka GO');
                utter.lang = 'id-ID';
                utter.rate = 0.95;
                utter.pitch = 1.05;
                utter.volume = 0.20; // Soft fallback speech volume (20%)

                const voices = window.speechSynthesis.getVoices();
                const idVoice = voices.find(v => v.lang && (v.lang.includes('id') || v.lang.includes('ID')));
                if (idVoice) utter.voice = idVoice;

                utter.onend = function() {
                    if (isRingtoneActive) {
                        setTimeout(() => {
                            if (isRingtoneActive) {
                                try { window.speechSynthesis.speak(utter); } catch(e) {}
                            }
                        }, 2000);
                    }
                };

                window.speechSynthesis.speak(utter);
            } catch (e) {}
        }
    }

    function stopRingtone() {
        isRingtoneActive = false;
        if ('speechSynthesis' in window) {
            try { window.speechSynthesis.cancel(); } catch (e) {}
        }
        if (ringtoneAudio) {
            try { ringtoneAudio.pause(); ringtoneAudio.currentTime = 0; } catch (e) {}
            ringtoneAudio = null;
        }
        if (outgoingAudio) {
            try { outgoingAudio.pause(); outgoingAudio.currentTime = 0; } catch (e) {}
            outgoingAudio = null;
        }
        if (ringtoneTimer) {
            clearInterval(ringtoneTimer);
            ringtoneTimer = null;
        }
        if (audioContext) {
            try { audioContext.close(); } catch (e) {}
            audioContext = null;
        }
    }

    // Public Voice Call Engine API
    window.CCGCall = {
        pendingAutoAnswer: false,

        init: function (orderCode) {
            if (orderCode) currentOrderCode = orderCode;
            injectCallUI();
            this.requestNotificationPermission();
            this.listenToServiceWorkerMessages();
            this.checkAutoAnswerUrl();
            this.startPolling();
        },

        listenToServiceWorkerMessages: function () {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && event.data.type === 'AUTO_ANSWER_CALL') {
                        this.pendingAutoAnswer = true;
                        if (currentCallId) {
                            this.answerCall();
                        }
                    }
                });
            }
        },

        checkAutoAnswerUrl: function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_answer') === 'true') {
                this.pendingAutoAnswer = true;
                const newUrl = window.location.pathname + window.location.search.replace(/[\?&]auto_answer=true/, '').replace(/^&/, '?');
                window.history.replaceState({}, '', newUrl || window.location.pathname);
            }
        },

        requestNotificationPermission: function () {
            if ("Notification" in window && Notification.permission === "default") {
                Notification.requestPermission();
            }
        },

        ensureMicPermission: async function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Browser Anda tidak mendukung fitur panggilan suara WebRTC.');
                return null;
            }

            // Permanently unlock audio element on user gesture
            unlockAudioElement();

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    },
                    video: false
                });

                // Ensure tracks are active
                if (stream) {
                    stream.getAudioTracks().forEach(t => { t.enabled = true; });
                }

                return stream;
            } catch (err) {
                console.error('[VoiceCall] Mic access denied/error:', err);
                const errMsg = 'Izin Mikrofon Dibutuhkan 🎙️\n\nBrowser Anda memblokir akses mikrofon.\nMohon klik ikon gembok 🔒 di sebelah alamat web (cicago.store) -> Setelan Situs / Izin -> Aktifkan Mikrofon.';
                if (window.AppAlert) {
                    window.AppAlert.show({
                        type: 'warning',
                        title: 'Izin Mikrofon Dibutuhkan 🎙️',
                        message: 'Browser Anda memblokir akses mikrofon. Klik ikon gembok 🔒 di sebelah alamat web -> Setelan Situs -> Aktifkan Mikrofon.',
                        confirmButtonText: 'Saya Mengerti'
                    });
                } else {
                    alert(errMsg);
                }
                return null;
            }
        },

        triggerSystemNotification: function (callData) {
            if ("Notification" in window && Notification.permission === "granted") {
                const title = "📞 Panggilan Suara Masuk - CicalengkaGO";
                const callerName = callData.caller_name || 'Pengguna';
                const currentUrl = window.location.href;
                const options = {
                    body: `${callerName} sedang menelepon Anda di CicalengkaGO. Ketuk untuk menjawab!`,
                    icon: callData.caller_avatar ? (window.BASE_URL + '/' + callData.caller_avatar) : (window.BASE_URL + '/assets/icons/icon-192.png'),
                    badge: window.BASE_URL + '/assets/icons/icon-192.png',
                    sound: (window.BASE_URL || '') + '/assets/audio/ringtone.wav',
                    tag: 'ccg-incoming-call-' + callData.id,
                    renotify: true,
                    requireInteraction: true,
                    priority: 'high',
                    vibrate: [500, 250, 500, 250, 500, 250, 500],
                    actions: [
                        { action: 'answer', title: '📞 Jawab Panggilan' },
                        { action: 'reject', title: '❌ Tolak' }
                    ],
                    data: {
                        url: currentUrl,
                        call_id: callData.id
                    }
                };

                if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.ready.then(reg => reg.showNotification(title, options));
                } else {
                    try {
                        const notif = new Notification(title, options);
                        notif.onclick = function () {
                            window.focus();
                            notif.close();
                        };
                    } catch (e) {}
                }
            }
        },

        // Send ICE candidate to backend
        sendIceCandidate: async function (candidate) {
            if (!currentCallId || !candidate) return;
            try {
                await fetch((window.BASE_URL || '') + '/calls/ice-candidate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_id: currentCallId,
                        candidate: JSON.stringify(candidate)
                    })
                });
            } catch (e) {}
        },

        // Flush pending ICE candidates once remoteDescription is set
        flushIceCandidates: async function (iceCandidatesData) {
            if (!iceCandidatesData || !peerConnection || !peerConnection.remoteDescription) return;
            try {
                const candidates = typeof iceCandidatesData === 'string' ? JSON.parse(iceCandidatesData) : iceCandidatesData;
                if (Array.isArray(candidates)) {
                    for (const candStr of candidates) {
                        const candKey = typeof candStr === 'string' ? candStr : JSON.stringify(candStr);
                        if (!processedCandidates.has(candKey)) {
                            processedCandidates.add(candKey);
                            const candObj = typeof candStr === 'string' ? JSON.parse(candStr) : candStr;
                            await peerConnection.addIceCandidate(new RTCIceCandidate(candObj));
                        }
                    }
                }
            } catch (e) {
                console.warn('[VoiceCall] Flush ICE candidates warning:', e);
            }
        },

        // Start Voice Call (Outgoing)
        makeCall: async function (orderCode, partnerName, partnerAvatar) {
            this.resetCall();
            currentOrderCode = orderCode || currentOrderCode;
            isCaller = true;
            processedCandidates.clear();

            // Immediately trigger UI and sound inside user click gesture!
            injectCallUI();

            const modal = document.getElementById('ccgVoiceCallModal');
            document.getElementById('ccgCallName').innerText = partnerName || 'Mitra Kurir';
            document.getElementById('ccgCallSubtext').innerText = 'Memanggil CicalengkaGO...';
            document.getElementById('ccgCallTimer').classList.add('d-none');
            document.getElementById('ccgSoundVisualizer').classList.add('d-none');

            if (partnerAvatar) {
                document.getElementById('ccgCallAvatar').src = partnerAvatar.startsWith('http') || partnerAvatar.startsWith('/')
                    ? partnerAvatar
                    : (window.BASE_URL + '/' + partnerAvatar);
            }

            document.getElementById('ccgIncomingActions').classList.add('d-none');
            document.getElementById('ccgActiveActions').classList.remove('d-none');
            modal.classList.remove('d-none');

            // Play outgoing tone IMMEDIATELY on user click
            playOutgoingTone();

            // Request microphone stream
            localStream = await this.ensureMicPermission();
            if (!localStream) {
                this.resetCall();
                return;
            }

            // Create WebRTC Offer
            let offerSdp = null;
            peerConnection = new RTCPeerConnection(rtcConfig);

            localStream.getTracks().forEach(track => {
                track.enabled = true;
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = attachAndPlayRemoteStream;

            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    window.CCGCall.sendIceCandidate(event.candidate);
                }
            };

            const offer = await peerConnection.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: false
            });
            await peerConnection.setLocalDescription(offer);
            offerSdp = JSON.stringify(offer);

            // Call Backend initiate API
            try {
                const res = await fetch((window.BASE_URL || '') + '/calls/initiate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_code: currentOrderCode,
                        offer: offerSdp
                    })
                });
                const data = await res.json();
                if (data.success) {
                    currentCallId = data.data.call_id;
                } else {
                    alert(data.message || 'Gagal memulai panggilan.');
                    this.resetCall();
                }
            } catch (e) {
                console.error('[VoiceCall] Initiate error:', e);
            }
        },

        // Incoming Call Received
        showIncomingCall: function (callData) {
            if (currentCallId && currentCallId === callData.id) {
                return; // Already handling this active incoming call
            }
            this.resetCall();
            currentCallId = callData.id;
            currentCallOffer = callData.offer;
            currentCallData = callData;
            isCaller = false;
            processedCandidates.clear();
            injectCallUI();

            const modal = document.getElementById('ccgVoiceCallModal');
            document.getElementById('ccgCallName').innerText = callData.caller_name || 'Panggilan Masuk';
            document.getElementById('ccgCallSubtext').innerText = 'Panggilan Suara Masuk...';
            document.getElementById('ccgCallTimer').classList.add('d-none');
            document.getElementById('ccgSoundVisualizer').classList.add('d-none');

            if (callData.caller_avatar) {
                document.getElementById('ccgCallAvatar').src = callData.caller_avatar.startsWith('http') || callData.caller_avatar.startsWith('/')
                    ? callData.caller_avatar
                    : (window.BASE_URL + '/' + callData.caller_avatar);
            }

            document.getElementById('ccgIncomingActions').classList.remove('d-none');
            document.getElementById('ccgActiveActions').classList.add('d-none');
            modal.classList.remove('d-none');

            playIncomingRingtone();

            try {
                if ("vibrate" in navigator && navigator.userActivation && navigator.userActivation.hasBeenActive) {
                    navigator.vibrate([300, 200, 300, 200, 300, 200]);
                }
            } catch (e) {}

            this.triggerSystemNotification(callData);

            if (this.pendingAutoAnswer) {
                this.pendingAutoAnswer = false;
                setTimeout(() => this.answerCall(), 400);
            }
        },

        // Answer Call (Receiver side)
        answerCall: async function () {
            stopRingtone();
            document.getElementById('ccgCallSubtext').innerText = 'Menghubungkan...';

            localStream = await this.ensureMicPermission();
            if (!localStream) {
                this.resetCall();
                return;
            }

            let answerSdp = null;
            peerConnection = new RTCPeerConnection(rtcConfig);

            localStream.getTracks().forEach(track => {
                track.enabled = true;
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = attachAndPlayRemoteStream;

            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    window.CCGCall.sendIceCandidate(event.candidate);
                }
            };

            // Set Remote Offer FIRST before calling createAnswer!
            if (currentCallOffer) {
                try {
                    const offerObj = typeof currentCallOffer === 'string' ? JSON.parse(currentCallOffer) : currentCallOffer;
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(offerObj));
                    
                    if (currentCallData && currentCallData.ice_candidates) {
                        await this.flushIceCandidates(currentCallData.ice_candidates);
                    }

                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    answerSdp = JSON.stringify(answer);
                } catch (e) {
                    console.error('[VoiceCall] Error creating answer from remote offer:', e);
                }
            }

            // Post answer to backend
            try {
                await fetch((window.BASE_URL || '') + '/calls/answer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_id: currentCallId,
                        answer: answerSdp
                    })
                });
            } catch (e) {}

            this.setCallConnected();
        },

        // Reject Call
        rejectCall: async function () {
            stopRingtone();
            if (currentCallId) {
                try {
                    await fetch((window.BASE_URL || '') + '/calls/reject', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ call_id: currentCallId })
                    });
                } catch (e) {}
            }
            this.resetCall();
        },

        // End Call
        endCall: async function () {
            stopRingtone();
            if (currentCallId) {
                try {
                    await fetch((window.BASE_URL || '') + '/calls/end', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ call_id: currentCallId })
                    });
                } catch (e) {}
            }
            this.resetCall();
        },

        // Set Connected State
        setCallConnected: function () {
            stopRingtone();
            document.getElementById('ccgCallSubtext').innerText = 'Terhubung';
            document.getElementById('ccgCallTimer').classList.remove('d-none');
            document.getElementById('ccgSoundVisualizer').classList.remove('d-none');
            document.getElementById('ccgIncomingActions').classList.add('d-none');
            document.getElementById('ccgActiveActions').classList.remove('d-none');

            const remoteAudio = document.getElementById('ccgRemoteAudio');
            if (remoteAudio) {
                remoteAudio.muted = false;
                remoteAudio.volume = 1.0;
                
                // Only attach receivers if srcObject is missing or has no active tracks
                if (!remoteAudio.srcObject || remoteAudio.srcObject.getAudioTracks().length === 0) {
                    if (peerConnection) {
                        const receivers = peerConnection.getReceivers();
                        if (receivers && receivers.length > 0) {
                            const tracks = receivers.map(r => r.track).filter(Boolean);
                            if (tracks.length > 0) {
                                tracks.forEach(t => { t.enabled = true; });
                                remoteAudio.srcObject = new MediaStream(tracks);
                            }
                        }
                    }
                }

                if (remoteAudio.srcObject) {
                    remoteAudio.srcObject.getAudioTracks().forEach(t => { t.enabled = true; });
                }

                if (remoteAudio.paused) {
                    remoteAudio.play().then(() => {
                        console.log('[VoiceCall] Connected remote audio playing smoothly.');
                    }).catch(e => {
                        console.warn('[VoiceCall] Connected audio play error:', e);
                    });
                }
            }

            this.startTimer();
        },

        // Toggle Microphone Mute
        toggleMute: function () {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    isMuted = !audioTrack.enabled;

                    const btn = document.getElementById('ccgBtnMute');
                    const label = document.getElementById('ccgMuteLabel');

                    if (isMuted) {
                        btn.classList.add('active');
                        label.innerText = 'Unmute';
                    } else {
                        btn.classList.remove('active');
                        label.innerText = 'Mute';
                    }
                }
            }
        },

        // Start Call Timer
        startTimer: function () {
            this.stopTimer();
            callDurationSeconds = 0;
            const timerEl = document.getElementById('ccgCallTimer');

            callTimerInterval = setInterval(() => {
                callDurationSeconds++;
                const mins = String(Math.floor(callDurationSeconds / 60)).padStart(2, '0');
                const secs = String(callDurationSeconds % 60).padStart(2, '0');
                if (timerEl) timerEl.innerText = `${mins}:${secs}`;
            }, 1000);
        },

        stopTimer: function () {
            if (callTimerInterval) {
                clearInterval(callTimerInterval);
                callTimerInterval = null;
            }
        },

        // Reset & Close Modal
        resetCall: function () {
            stopRingtone();
            this.stopTimer();

            if (peerConnection) {
                try { peerConnection.close(); } catch (e) {}
                peerConnection = null;
            }
            if (localStream) {
                try { localStream.getTracks().forEach(t => t.stop()); } catch (e) {}
                localStream = null;
            }

            const remoteAudio = document.getElementById('ccgRemoteAudio');
            if (remoteAudio) {
                try { remoteAudio.pause(); remoteAudio.srcObject = null; } catch(e) {}
            }

            currentCallId = null;
            currentCallOffer = null;
            currentCallData = null;
            processedCandidates.clear();
            isMuted = false;
            isCaller = false;
            this.pendingAutoAnswer = false;

            const modal = document.getElementById('ccgVoiceCallModal');
            if (modal) modal.classList.add('d-none');
        },

        // Poll backend for call signals
        startPolling: function () {
            if (pollInterval) clearInterval(pollInterval);

            pollInterval = setInterval(async () => {
                const url = currentOrderCode
                    ? ((window.BASE_URL || '') + `/calls/poll?order_code=${encodeURIComponent(currentOrderCode)}`)
                    : ((window.BASE_URL || '') + `/calls/poll`);

                try {
                    const res = await fetch(url);
                    const data = await res.json();

                    if (!data.success || !data.data || !data.data.active_call) {
                        if (currentCallId) this.resetCall();
                        return;
                    }

                    const activeCall = data.data.active_call;
                    currentCallData = activeCall;

                    // 1. Incoming call for receiver
                    if (activeCall.status === 'calling' && !isCaller && !currentCallId) {
                        this.showIncomingCall(activeCall);
                    }

                    // 2. Caller receives Answer SDP from Receiver
                    if (activeCall.status === 'connected' && isCaller && peerConnection) {
                        if (activeCall.answer && peerConnection.signalingState === 'have-local-offer') {
                            try {
                                const answerObj = typeof activeCall.answer === 'string' ? JSON.parse(activeCall.answer) : activeCall.answer;
                                await peerConnection.setRemoteDescription(new RTCSessionDescription(answerObj));
                                await this.flushIceCandidates(activeCall.ice_candidates);
                                this.setCallConnected();
                            } catch (e) {
                                console.error('[VoiceCall] Error setting remote answer:', e);
                            }
                        }
                    }

                    // 3. Process ICE Candidates (Only when remoteDescription is set)
                    if (activeCall.ice_candidates && peerConnection && peerConnection.remoteDescription) {
                        await this.flushIceCandidates(activeCall.ice_candidates);
                    }

                    // 4. Call ended or rejected
                    if (activeCall.status === 'rejected' || activeCall.status === 'ended') {
                        if (currentCallId) this.resetCall();
                    }
                } catch (e) {}
            }, 2500);
        }
    };

    // Auto-init call engine listener on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.CCGCall.init());
    } else {
        window.CCGCall.init();
    }
})();
