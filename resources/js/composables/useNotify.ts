export default function useNotify() {
    // lightweight wrapper around global toastr to keep components concise
    // avoids sprinkling try/catch everywhere
    // @ts-ignore
    const t = typeof window !== 'undefined' ? (window as any).toastr : null;

    function call(type: 'success' | 'error' | 'info' | 'warning', msg: string) {
        try {
            if (!t) return;
            const fn = t[type];
            if (typeof fn === 'function') fn(String(msg));
        } catch (e) {
            // noop
        }
    }

    return {
        success: (m: string) => call('success', m),
        error: (m: string) => call('error', m),
        info: (m: string) => call('info', m),
        warn: (m: string) => call('warning', m),
    };
}
