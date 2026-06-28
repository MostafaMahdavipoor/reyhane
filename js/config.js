const COURSE_CONFIG = {
    deadline: new Date('2026-07-04T20:29:59Z'),
    maxCapacity: 20,
    price: 2500000,
    originalPrice: 3000000,
    cardNumber: '6104337364737526',
    cardNumberFormatted: '6104-3373-6473-7526',
    cardHolder: 'ریحانه زراعتکار',
    schedules: {
        1: 'شنبه، دوشنبه و چهارشنبه',
        2: 'سه‌شنبه و پنج‌شنبه',
    },
};

function formatPrice(amount) {
    return amount.toLocaleString('fa-IR') + ' تومان';
}

function isRegistrationOpen() {
    return Date.now() < COURSE_CONFIG.deadline.getTime();
}

function getTimeRemaining() {
    const diff = COURSE_CONFIG.deadline.getTime() - Date.now();
    if (diff <= 0) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true };
    }
    return {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((diff / (1000 * 60)) % 60),
        seconds: Math.floor((diff / 1000) % 60),
        expired: false,
    };
}

function toPersianDigits(num) {
    return String(num).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
}

function initCountdown(containerId, onExpired) {
    const container = document.getElementById(containerId);
    if (!container) return;

    function update() {
        const t = getTimeRemaining();
        if (t.expired) {
            container.classList.add('expired');
            container.innerHTML = `
                <div class="countdown-item"><span class="num">۰</span><span class="label">روز</span></div>
                <div class="countdown-item"><span class="num">۰</span><span class="label">ساعت</span></div>
                <div class="countdown-item"><span class="num">۰</span><span class="label">دقیقه</span></div>
                <div class="countdown-item"><span class="num">۰</span><span class="label">ثانیه</span></div>
            `;
            if (onExpired) onExpired();
            return;
        }
        container.innerHTML = `
            <div class="countdown-item"><span class="num">${toPersianDigits(t.days)}</span><span class="label">روز</span></div>
            <div class="countdown-item"><span class="num">${toPersianDigits(t.hours)}</span><span class="label">ساعت</span></div>
            <div class="countdown-item"><span class="num">${toPersianDigits(t.minutes)}</span><span class="label">دقیقه</span></div>
            <div class="countdown-item"><span class="num">${toPersianDigits(t.seconds)}</span><span class="label">ثانیه</span></div>
        `;
    }

    update();
    setInterval(update, 1000);
}

async function fetchCourseStatus() {
    try {
        const res = await fetch('/api/course/status');
        if (!res.ok) throw new Error('failed');
        return await res.json();
    } catch {
        return null;
    }
}

function updateCapacityDisplay(data) {
    const el = document.getElementById('capacity-info');
    const fill = document.getElementById('capacity-fill');
    if (!el || !data) return;

    const remaining = data.remaining ?? COURSE_CONFIG.maxCapacity;
    const registered = data.registered ?? 0;
    const max = data.maxCapacity ?? COURSE_CONFIG.maxCapacity;
    const pct = Math.min(100, (registered / max) * 100);

    el.textContent = `${toPersianDigits(remaining)} جای خالی از ${toPersianDigits(max)} نفر`;
    if (fill) fill.style.width = pct + '%';
}
