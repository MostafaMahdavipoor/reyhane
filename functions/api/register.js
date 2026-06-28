import { getConfig, isRegistrationOpen, jsonResponse, errorResponse } from '../_lib/config.js';

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_FILE_SIZE = 5 * 1024 * 1024;

function normalizePhone(phone) {
    let p = phone.replace(/\s/g, '').replace(/^\+98/, '0');
    if (p.startsWith('98') && p.length === 12) p = '0' + p.slice(2);
    return p;
}

function isValidPhone(phone) {
    return /^09\d{9}$/.test(phone);
}

export async function onRequestPost(context) {
    const { request, env } = context;
    const config = getConfig(env);

    if (!isRegistrationOpen()) {
        return errorResponse('مهلت ثبت‌نام به پایان رسیده است.', 403);
    }

    let formData;
    try {
        formData = await request.formData();
    } catch {
        return errorResponse('فرمت درخواست نامعتبر است.');
    }

    const fullName = (formData.get('full_name') || '').trim();
    const phone = normalizePhone(formData.get('phone') || '');
    const telegram = (formData.get('telegram') || '').trim();
    const scheduleCode = parseInt(formData.get('schedule_code'), 10);
    const receipt = formData.get('receipt');

    if (!fullName || fullName.length < 3) {
        return errorResponse('نام و نام خانوادگی را وارد کنید.');
    }
    if (!isValidPhone(phone)) {
        return errorResponse('شماره موبایل معتبر نیست. (مثال: ۰۹۱۲۳۴۵۶۷۸۹)');
    }
    if (![1, 2].includes(scheduleCode)) {
        return errorResponse('کد آموزشی را انتخاب کنید.');
    }
    if (!receipt || typeof receipt === 'string') {
        return errorResponse('تصویر فیش واریزی را بارگذاری کنید.');
    }

    if (!ALLOWED_TYPES.includes(receipt.type)) {
        return errorResponse('فرمت فایل مجاز نیست. (jpg, png, webp)');
    }
    if (receipt.size > MAX_FILE_SIZE) {
        return errorResponse('حجم فایل نباید بیشتر از ۵ مگابایت باشد.');
    }

    const countResult = await env.DB.prepare(
        "SELECT COUNT(*) as count FROM registrations WHERE status IN ('pending', 'approved')"
    ).first();
    const registered = countResult?.count ?? 0;

    if (registered >= config.maxCapacity) {
        return errorResponse('ظرفیت دوره تکمیل شده است.', 403);
    }

    const existing = await env.DB.prepare(
        'SELECT id FROM registrations WHERE phone = ?'
    ).bind(phone).first();

    if (existing) {
        return errorResponse('این شماره موبایل قبلاً ثبت‌نام کرده است.');
    }

    const ext = receipt.type === 'image/png' ? 'png'
        : receipt.type === 'image/webp' ? 'webp' : 'jpg';
    const receiptKey = `receipts/${crypto.randomUUID()}.${ext}`;

    try {
        await env.RECEIPTS.put(receiptKey, receipt.stream(), {
            httpMetadata: { contentType: receipt.type },
        });
    } catch {
        return errorResponse('خطا در ذخیره فیش. لطفاً دوباره تلاش کنید.', 500);
    }

    try {
        await env.DB.prepare(
            `INSERT INTO registrations (full_name, phone, telegram, schedule_code, receipt_key, status)
             VALUES (?, ?, ?, ?, ?, 'pending')`
        ).bind(fullName, phone, telegram || null, scheduleCode, receiptKey).run();
    } catch (err) {
        try { await env.RECEIPTS.delete(receiptKey); } catch { /* ignore */ }
        return errorResponse('خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید.', 500);
    }

    return jsonResponse({
        success: true,
        message: 'ثبت‌نام شما با موفقیت انجام شد. پس از بررسی فیش، نتیجه از طریق تلگرام اطلاع‌رسانی می‌شود.',
    }, 201);
}

export async function onRequestOptions() {
    return new Response(null, {
        status: 204,
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type',
        },
    });
}
