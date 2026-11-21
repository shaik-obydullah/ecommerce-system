const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8080';

export default function Home() {
    return (
        <main
            style={{
                width: 'min(40rem, 100%)',
                padding: '2.5rem',
                background: '#ffffff',
                border: '1px solid #e4e4e0',
                borderRadius: '1.25rem',
            }}
        >
            <p style={{ margin: 0, color: '#b45309', fontWeight: 700, letterSpacing: '0.12em', textTransform: 'uppercase', fontSize: '0.8rem' }}>
                Phase 0 placeholder
            </p>
            <h1 style={{ margin: '0.5rem 0 0.75rem', fontSize: '2.2rem', lineHeight: 1.15 }}>
                Next.js storefront
            </h1>
            <p style={{ margin: '0 0 1.25rem', color: '#5b6470' }}>
                The headless storefront arrives in Phase 5. The Laravel core already owns the
                API, admin, and checkout domain.
            </p>
            <nav style={{ display: 'flex', gap: '0.75rem', flexWrap: 'wrap' }}>
                <a href={`${apiUrl}/api/v1/health`} style={linkStyle}>
                    Core health
                </a>
                <a href={`${apiUrl}/admin`} style={linkStyle}>
                    Admin panel
                </a>
            </nav>
        </main>
    );
}

const linkStyle = {
    padding: '0.6rem 0.95rem',
    border: '1px solid #e4e4e0',
    borderRadius: '0.6rem',
    color: '#1f2328',
    fontWeight: 600,
    textDecoration: 'none',
};
