import { jsonResponse, errorResponse } from '../../_lib/config.js';
import { signAdminToken, verifyAdmin } from '../../_lib/auth.js';

export async function onRequest(context) {
    const { request, env, params } = context;
    const path = params.path || [];
    const method = request.method;

    if (path[0] === 'login' && method === 'POST') {
        return handleLogin(request, env);
    }

    const authed = await verifyAdmin(request, env);
    if (!authed) {
        return errorResponse('دسترسی غیرمجاز.', 401);
    }

    if (path[0] === 'registrations' && path.length === 1) {
        if (method === 'GET') return listRegistrations(env, request);
        return errorResponse('Method not allowed', 405);
    }

    if (path[0] === 'registrations' && path.length === 2 && method === 'PATCH') {
        return updateRegistration(env, path[1], request);
    }

    if (path[0] === 'receipt' && path.length === 2 && method === 'GET') {
        return getReceipt(env, path[1]);
    }

    return errorResponse('Not found', 404);
}

async function handleLogin(request, env) {
    let body;
    try {
        body = await request.json();
    } catch {
        return errorResponse('درخواست نامعتبر.');
    }

    if (!env.ADMIN_PASSWORD) {
        return errorResponse('رمز ادمین تنظیم نشده است.', 500);
    }

    if (body.password !== env.ADMIN_PASSWORD) {
        return errorResponse('رمز عبور اشتباه است.', 401);
    }

    const token = await signAdminToken(env);
    return jsonResponse({ success: true, token });
}

async function listRegistrations(env, request) {
    const url = new URL(request.url);
    const status = url.searchParams.get('status');

    let query = 'SELECT * FROM registrations';
    const bindings = [];

    if (status && ['pending', 'approved', 'rejected'].includes(status)) {
        query += ' WHERE status = ?';
        bindings.push(status);
    }

    query += ' ORDER BY created_at DESC';

    const stmt = env.DB.prepare(query);
    const result = bindings.length
        ? await stmt.bind(...bindings).all()
        : await stmt.all();

    const stats = await env.DB.prepare(`
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status IN ('pending', 'approved') THEN 1 ELSE 0 END) as active
        FROM registrations
    `).first();

    return jsonResponse({
        success: true,
        registrations: result.results || [],
        stats: {
            total: stats?.total ?? 0,
            pending: stats?.pending ?? 0,
            approved: stats?.approved ?? 0,
            rejected: stats?.rejected ?? 0,
            active: stats?.active ?? 0,
            maxCapacity: parseInt(env.MAX_CAPACITY || '20', 10),
        },
    });
}

async function updateRegistration(env, id, request) {
    let body;
    try {
        body = await request.json();
    } catch {
        return errorResponse('درخواست نامعتبر.');
    }

    const { status, admin_note } = body;
    if (!['approved', 'rejected', 'pending'].includes(status)) {
        return errorResponse('وضعیت نامعتبر.');
    }

    const existing = await env.DB.prepare(
        'SELECT id FROM registrations WHERE id = ?'
    ).bind(id).first();

    if (!existing) {
        return errorResponse('ثبت‌نام یافت نشد.', 404);
    }

    await env.DB.prepare(
        `UPDATE registrations SET status = ?, admin_note = ?, reviewed_at = datetime('now') WHERE id = ?`
    ).bind(status, admin_note || null, id).run();

    return jsonResponse({ success: true, message: 'وضعیت به‌روزرسانی شد.' });
}

async function getReceipt(env, key) {
    const decodedKey = decodeURIComponent(key);
    if (!decodedKey.startsWith('receipts/')) {
        return errorResponse('کلید نامعتبر.', 400);
    }

    const object = await env.RECEIPTS.get(decodedKey);
    if (!object) {
        return errorResponse('فیش یافت نشد.', 404);
    }

    const headers = new Headers();
    object.writeHttpMetadata(headers);
    headers.set('Cache-Control', 'private, max-age=3600');

    return new Response(object.body, { headers });
}

export async function onRequestOptions() {
    return new Response(null, {
        status: 204,
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PATCH, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
        },
    });
}
