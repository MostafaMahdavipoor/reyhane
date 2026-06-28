export async function signAdminToken(env) {
    const password = env.ADMIN_PASSWORD;
    if (!password) return null;

    const key = await crypto.subtle.importKey(
        'raw',
        new TextEncoder().encode(password),
        { name: 'HMAC', hash: 'SHA-256' },
        false,
        ['sign']
    );
    const sig = await crypto.subtle.sign(
        'HMAC',
        key,
        new TextEncoder().encode('reyhane-admin-v1')
    );
    return btoa(String.fromCharCode(...new Uint8Array(sig)));
}

export async function verifyAdmin(request, env) {
    const auth = request.headers.get('Authorization');
    if (!auth?.startsWith('Bearer ')) return false;

    const token = auth.slice(7);
    const expected = await signAdminToken(env);
    if (!expected) return false;

    return token === expected;
}
