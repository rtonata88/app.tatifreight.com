# Nexus auth — UI kit

Sign-in, password reset and access request, recreated from `resources/views/auth/*` and
`routes/auth.php`.

Open `index.html`.

## Layout

A two-panel split: the left half is the darkest rail surface carrying the wordmark, one
italic display-serif line of positioning, a brass hairline and a compliance note; the right
half holds the form at a 380px column. Inputs are bare — label above, hairline below, brass
on focus, oxblood on error.

| File | Screens |
|---|---|
| `AuthScreens.jsx` | `LoginScreen`, `ForgotScreen`, `RegisterScreen` |

## Notes

- Submitting the sign-in form with an empty password shows the error treatment; with a
  password it fires the success toast.
- Registration is modelled as an **access request** — the app assigns roles through Spatie
  permissions, so self-service signup would be wrong.
- Two-factor is not in the repo's auth routes, so no 2FA screen is included. Ask and it can
  be added in the same register.
