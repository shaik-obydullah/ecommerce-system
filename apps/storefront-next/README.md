# Next.js storefront

Headless storefront for the ecommerce core. This is a Phase 0 placeholder; the
real catalog, cart, and checkout UI land in Phase 5.

```bash
npm install
npm run dev
```

The app expects the core API at `NEXT_PUBLIC_API_URL` (default
`http://localhost:8080`). It exposes `/api/health` for container health checks.
