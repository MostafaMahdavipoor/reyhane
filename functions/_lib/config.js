export const DEADLINE_ISO = '2026-07-04T20:29:59Z';

export function getConfig(env) {
    return {
        maxCapacity: parseInt(env.MAX_CAPACITY || '20', 10),
        coursePrice: parseInt(env.COURSE_PRICE || '2500000', 10),
        cardNumber: env.CARD_NUMBER || '6104337364737526',
        cardHolder: env.CARD_HOLDER || 'ریحانه زراعتکار',
        adminPassword: env.ADMIN_PASSWORD || '',
    };
}

export function isRegistrationOpen() {
    return Date.now() < new Date(DEADLINE_ISO).getTime();
}

export function jsonResponse(data, status = 200) {
    return new Response(JSON.stringify(data), {
        status,
        headers: {
            'Content-Type': 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin': '*',
        },
    });
}

export function errorResponse(message, status = 400) {
    return jsonResponse({ success: false, error: message }, status);
}
