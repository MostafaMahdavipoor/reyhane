import { getConfig, isRegistrationOpen, jsonResponse } from '../../_lib/config.js';

export async function onRequestGet(context) {
    const { env } = context;
    const config = getConfig(env);

    let registered = 0;
    try {
        const result = await env.DB.prepare(
            "SELECT COUNT(*) as count FROM registrations WHERE status IN ('pending', 'approved')"
        ).first();
        registered = result?.count ?? 0;
    } catch {
        registered = 0;
    }

    const remaining = Math.max(0, config.maxCapacity - registered);
    const open = isRegistrationOpen() && remaining > 0;

    return jsonResponse({
        success: true,
        registered,
        remaining,
        maxCapacity: config.maxCapacity,
        registrationOpen: open,
        deadline: '2026-07-04T20:29:59Z',
        price: config.coursePrice,
        originalPrice: 3000000,
        cardNumber: config.cardNumber.replace(/(\d{4})/g, '$1-').slice(0, -1),
        cardHolder: config.cardHolder,
    });
}
