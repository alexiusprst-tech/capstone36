# UI redesign — 6 November 2026

The interface follows the supplied dashboard reference: a light sidebar, white panels, subtle borders, blue navigation and primary actions, compact typography, and semantic status colors. The Alpharo identity and existing Indonesian business content remain in place.

## Coverage

- Shared navigation, breadcrumbs, notification and role menus, account controls, and mobile navigation.
- Marketing, Director, Administration, Warehouse, and Finance views, including create/edit forms, monitoring, reports, and SPJ interfaces.
- Public partner portal, supplier portal, authentication, profiles, and notifications.
- KPI cards, tables, filters, empty states, badges, timelines, document checklists, toasts, modals, drawers, and the quick customer form.
- Modal keyboard focus management, Escape dismissal, focus restoration, scroll locking, visible focus indicators, and reduced-motion support.

## Presentation boundaries

Business scripts in all 50 views were compared with a snapshot taken before the redesign. Their content, form bindings, conditions, loops, and event handlers were also compared. Router, store, and service files were kept identical to that snapshot, including pre-existing local changes. No PHP, API contract, approval threshold, financial calculation, data record, or role rule was changed by this work. Document generation/export code was retained.

This preserves the existing application behavior; it does not certify or resolve existing differences between business-process documents and the implementation.

## Theme and tooling

Theme tokens and shared styles live in `frontend/src/style.css`. Tailwind was migrated to v4 through `@tailwindcss/vite`; the old JavaScript theme configuration is replaced by CSS `@theme` tokens. Status badges retain green, amber, blue, and red semantics. Wide tables scroll within their panels on small screens; empty-state messages remain visible in the initial viewport.

## Verification

From `frontend`:

```sh
npm run build
npm run test:ui
```

The browser suite discovers all 42 active route screens and exercises desktop (1440px) and mobile (390px) rendering. It also covers role redirects, unauthenticated redirects, navigation, notifications, public portal tabs, director report categories, procurement and supplier tabs, searching and empty states, payment dialogs, detail drawers, the quick customer form, responsive Excel exports, and dashboard activity visibility. API requests are isolated during these tests so they cannot modify real transactions. Screenshots and failure traces are written to ignored `test-results/`; the HTML report is in ignored `playwright-report/`.

Windows uses the installed Microsoft Edge browser by default. Other platforms use Playwright Chromium (install it with `npx playwright install chromium`); `PLAYWRIGHT_CHANNEL` can select another installed channel.

The application now uses Laravel + Inertia 2 + Vue 3. Laravel owns page routes and session authentication; the existing transaction APIs remain in use. Vite builds separate page chunks into `backend/public/build`, and Blade loads the manifest through `@vite`. Stable public assets are copied into `backend/public`; only the generated build directory is refreshed. See [INERTIA.md](INERTIA.md) for local startup and verification commands.

## Dashboard activity logs

The five internal dashboards share `ActivityLogCard.vue`. Marketing, Administration, Warehouse and Finance see entries authored by their own role/division. The Director retains the complete system audit, including partner activity, and can open the existing audit report. External or unknown roles cannot display the internal activity panel.

Each panel shows the action, timestamp, user, role, reference and description. Entries are sorted newest first and paginated five at a time, with no search control. Counts are calculated after role filtering. New entries in the reactive store appear while the dashboard is open; empty states are included.

This feature uses the existing `store.activityLogs` and `storeActions.addActivityLog` stream and its existing browser persistence. It does not add a backend audit API or change server authorization. Existing transaction actions continue to record activity as before.

Run `npm run test:unit` for division filtering and ordering checks, and `npm run test:ui -- activity-logs.spec.js` for all five roles on desktop and mobile, including role visibility across pages, pagination, empty states and new log entries.

## Dashboard card layout

The five internal dashboard workspaces use a single full-width card flow at every screen size. Each card fills the content width and follows the preceding card with a 16–20px gap, so uneven column heights cannot leave an unused area beside the final card. The role-scoped activity panel is the last card, immediately followed by normal page padding and the footer.

KPI rows and paired charts retain their existing layout. Dashboard panel interiors use full-width data rows and thin separators for notifications, stock movements, stock alerts, documents, receivables and approval history. Operational division summaries use plain columns with separators. Rounded backgrounds and borders are reserved for the outer panels and actual interactive controls.

Dashboard status labels use the shared `StatusBadge` plain variant: semantic text color and a dot, without a surrounding box. Other screens retain their existing badge variant. KPI icons and trends, and activity role labels, are also unboxed. Descriptions wrap naturally instead of being truncated.

Long desktop lists and tables scroll within their card bodies. At either end, wheel scrolling continues into the page instead of getting trapped over the panel. The activity body is capped at 240px on desktop, with pagination remaining outside the scroll area; changing the page resets the list's scroll position. Smaller screens keep the natural vertical flow. Card content, dashboard scripts, event handlers and role scoping remain unchanged.

`npm run test:ui -- dashboard-layout.spec.js activity-logs.spec.js` checks sparse and populated dashboards, full-width cards, gaps, overlap, absence of nested decorative panels, plain status labels, footer proximity, scrolling, mobile/tablet behavior and activity permissions.


## Company profile ? 8 November 2026

The public company profile is now a separate presentation component, `frontend/src/components/company/CompanyProfile.vue`. The supplied references inform its two-column hero, large heading, serif emphasis, aligned service columns, and restrained section spacing. Alpharo keeps its blue palette, company facts, services, vision, mission, office details, bank account, and existing portal actions.

The public header keeps all four service destinations visible, including on mobile, with an active-page indicator and a staff login link. A single hero replaces the repeated introductions. Facts and services use separators rather than nested cards. Photos are locally stored illustrations, explicitly captioned as such; source URLs are recorded in `frontend/public/images/company/SOURCES.md`. The location panel displays the embedded Google map directly, without an activation button, and also offers a direct Maps link.

`company-profile.spec.js` covers responsive layouts from 320 to 1920 pixels, local image loading, navigation reachability, direct map display, and form preservation through profile/request/tracking/payment switches. Review screenshots are written to ignored `.ui-review/company-profile/`.
