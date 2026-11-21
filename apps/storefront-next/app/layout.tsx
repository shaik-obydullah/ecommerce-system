import type { Metadata } from 'next';
import type { ReactNode } from 'react';

export const metadata: Metadata = {
    title: 'Ecommerce',
    description: 'Headless storefront for the ecommerce core.',
};

export default function RootLayout({ children }: { children: ReactNode }) {
    return (
        <html lang="en">
            <body
                style={{
                    margin: 0,
                    minHeight: '100vh',
                    display: 'grid',
                    placeItems: 'center',
                    background: '#f7f7f5',
                    color: '#1f2328',
                    fontFamily: 'ui-sans-serif, system-ui, sans-serif',
                }}
            >
                {children}
            </body>
        </html>
    );
}
