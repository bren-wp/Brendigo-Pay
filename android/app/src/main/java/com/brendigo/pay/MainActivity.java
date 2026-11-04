package com.brendigo.pay;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.LinearGradient;
import android.graphics.Paint;
import android.graphics.Path;
import android.graphics.Shader;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
import android.os.Bundle;
import android.text.InputType;
import android.util.TypedValue;
import android.view.Gravity;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.ScrollView;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;
import org.json.JSONArray;
import org.json.JSONObject;
import java.math.BigDecimal;
import java.text.NumberFormat;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.Date;
import java.util.Locale;
import java.util.TimeZone;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * Native user panel backed by the same merchant data as web and WordPress.
 * Read-only transaction data; payment-link creation uses the server-side paid license.
 * Never collects payment card numbers or enables production charges.
 */
public final class MainActivity extends Activity {
    private static final int NAVY = Color.rgb(15, 23, 42);
    private static final int SIDEBAR = Color.rgb(16, 30, 87);
    private static final int BLUE = Color.rgb(49, 91, 250);
    private static final int BG = Color.rgb(243, 246, 251);
    private static final int MUTED = Color.rgb(100, 116, 139);
    private static final int STROKE = Color.rgb(220, 228, 244);
    private static final int GREEN = Color.rgb(24, 153, 69);
    private static final String[] TABS = {"Pregled", "Transakcije", "Linkovi", "Web-stranice"};
    private final ExecutorService worker = Executors.newSingleThreadExecutor();
    private SecureStore storage;
    private LinearLayout screen;
    private LinearLayout content;
    private JSONObject snapshot;
    private String selectedTab = "Pregled";
    private boolean loading;

    @Override public void onCreate(Bundle saved) {
        super.onCreate(saved);
        storage = new SecureStore(this);
        getWindow().setStatusBarColor(Color.rgb(22, 39, 131));
        getWindow().setNavigationBarColor(SIDEBAR);
        if (storage.token() == null) showLogin("");
        else refresh();
    }

    @Override protected void onDestroy() {
        worker.shutdownNow();
        super.onDestroy();
    }

    private int dp(float x) { return (int) (x * getResources().getDisplayMetrics().density + .5f); }
    private int widthDp() { return (int) (getResources().getDisplayMetrics().widthPixels / getResources().getDisplayMetrics().density); }
    private LinearLayout vertical() {
        LinearLayout v = new LinearLayout(this);
        v.setOrientation(LinearLayout.VERTICAL);
        return v;
    }
    private LinearLayout horizontal() {
        LinearLayout v = new LinearLayout(this);
        v.setOrientation(LinearLayout.HORIZONTAL);
        v.setGravity(Gravity.CENTER_VERTICAL);
        return v;
    }
    private GradientDrawable surface(int color, int radius, int border) {
        GradientDrawable d = new GradientDrawable();
        d.setColor(color);
        d.setCornerRadius(dp(radius));
        if (border != 0) d.setStroke(dp(1), border);
        return d;
    }
    private GradientDrawable gradient(int radius) {
        GradientDrawable g = new GradientDrawable(GradientDrawable.Orientation.TL_BR,
                new int[]{Color.rgb(59, 107, 255), Color.rgb(13, 40, 211)});
        g.setCornerRadius(dp(radius));
        return g;
    }
    private TextView label(String value, int size, int color, boolean bold) {
        TextView t = new TextView(this);
        t.setText(value);
        t.setTextColor(color);
        t.setTextSize(TypedValue.COMPLEX_UNIT_SP, size);
        t.setTypeface(Typeface.create("sans-serif", bold ? Typeface.BOLD : Typeface.NORMAL));
        t.setGravity(Gravity.CENTER_VERTICAL);
        t.setIncludeFontPadding(false);
        t.setLineSpacing(dp(2), 1f);
        return t;
    }
    private TextView button(String title, boolean primary) {
        TextView b = label(title, 14, primary ? Color.WHITE : BLUE, true);
        b.setGravity(Gravity.CENTER);
        b.setMinHeight(dp(46));
        b.setPadding(dp(14), dp(12), dp(14), dp(12));
        b.setBackground(primary ? gradient(12) : surface(Color.WHITE, 12, STROKE));
        b.setClickable(true);
        b.setFocusable(true);
        return b;
    }
    private void margin(View view, int left, int top, int right, int bottom) {
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(-1, -2);
        p.setMargins(dp(left), dp(top), dp(right), dp(bottom));
        view.setLayoutParams(p);
    }
    private LinearLayout card(int padding) {
        LinearLayout box = vertical();
        box.setPadding(dp(padding), dp(padding), dp(padding), dp(padding));
        box.setBackground(surface(Color.WHITE, 17, STROKE));
        return box;
    }
    private void base() {
        screen = vertical();
        screen.setBackgroundColor(BG);
        screen.setFitsSystemWindows(true);
        setContentView(screen);
    }
    private void addIcon(LinearLayout dest, int size) {
        ImageView icon = new ImageView(this);
        icon.setImageResource(R.drawable.brand_icon);
        icon.setScaleType(ImageView.ScaleType.FIT_CENTER);
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(dp(size), dp(size));
        params.setMargins(0, 0, dp(10), 0);
        dest.addView(icon, params);
    }
    private void header(boolean authenticated) {
        LinearLayout bar = horizontal();
        bar.setBackgroundColor(Color.WHITE);
        bar.setPadding(dp(20), dp(12), dp(18), dp(12));
        bar.setElevation(dp(3));
        addIcon(bar, 38);
        LinearLayout brand = vertical();
        brand.addView(label("Brendigo Pay", 19, NAVY, true));
        brand.addView(label("PAMETNA PLAĆANJA · SANDBOX", 9, BLUE, true));
        bar.addView(brand, new LinearLayout.LayoutParams(0, -2, 1));
        if (authenticated) {
            TextView account = button("Račun", false);
            account.setMinHeight(dp(38));
            account.setOnClickListener(v -> showProfile());
            bar.addView(account);
        }
        screen.addView(bar, new LinearLayout.LayoutParams(-1, dp(67)));
    }
    private void makeScroll() {
        ScrollView scroll = new ScrollView(this);
        scroll.setFillViewport(false);
        scroll.setClipToPadding(false);
        content = vertical();
        content.setPadding(dp(16), dp(18), dp(16), dp(28));
        scroll.addView(content);
        screen.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));
    }
    private EditText field(String hint, boolean password) {
        EditText input = new EditText(this);
        input.setTextSize(TypedValue.COMPLEX_UNIT_SP, 15);
        input.setTextColor(NAVY);
        input.setSingleLine(true);
        input.setHint(hint);
        input.setHintTextColor(MUTED);
        input.setPadding(dp(14), dp(12), dp(14), dp(12));
        input.setBackground(surface(Color.rgb(251, 252, 255), 10, STROKE));
        input.setInputType(password ? InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD
                : InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS);
        input.setMinHeight(dp(48));
        return input;
    }
    private void showLogin(String error) {
        snapshot = null;
        base();
        header(false);
        makeScroll();
        LinearLayout welcome = card(23);
        margin(welcome, 0, 27, 0, 0);
        LinearLayout logo = horizontal();
        addIcon(logo, 55);
        logo.addView(label("Brendigo Pay", 24, NAVY, true));
        welcome.addView(logo);
        TextView badge = label("SIGURAN KORISNIČKI PANEL", 11, BLUE, true);
        margin(badge, 0, 25, 0, 10);
        welcome.addView(badge);
        TextView title = label("Prijavite se na svoj račun", 25, NAVY, true);
        margin(title, 0, 0, 0, 9);
        welcome.addView(title);
        TextView sub = label("Isti podaci kao na webu. Pregledajte transakcije, platne linkove i licence svojih stranica.", 14, MUTED, false);
        margin(sub, 0, 0, 0, 20);
        welcome.addView(sub);
        if (!error.isEmpty()) {
            TextView warning = label(error, 13, Color.rgb(144, 38, 38), true);
            warning.setPadding(dp(12), dp(12), dp(12), dp(12));
            warning.setBackground(surface(Color.rgb(255, 238, 239), 10, 0));
            margin(warning, 0, 0, 0, 15);
            welcome.addView(warning);
        }
        welcome.addView(label("HTTPS adresa platforme", 13, NAVY, true));
        EditText server = field("https://brendigopay.com", false);
        server.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_URI);
        server.setText(storage.origin());
        margin(server, 0, 7, 0, 16);
        welcome.addView(server);
        welcome.addView(label("E-mail adresa", 13, NAVY, true));
        EditText email = field("vas@email.hr", false);
        margin(email, 0, 7, 0, 16);
        welcome.addView(email);
        welcome.addView(label("Lozinka", 13, NAVY, true));
        EditText password = field("Najmanje 12 znakova", true);
        margin(password, 0, 7, 0, 23);
        welcome.addView(password);
        TextView login = button("Prijavi se  →", true);
        welcome.addView(login);
        login.setOnClickListener(v -> {
            String url = ApiClient.origin(server.getText().toString());
            if (url == null) {
                showError("Unesite HTTPS adresu platforme bez dodatnog puta.");
                return;
            }
            if (email.getText().length() == 0 || password.getText().length() == 0) {
                showError("Unesite e-mail adresu i lozinku.");
                return;
            }
            if (loading) return;
            loading = true;
            login.setEnabled(false);
            login.setText("Prijavljivanje...");
            final String enteredEmail = email.getText().toString().trim();
            final String enteredPassword = password.getText().toString();
            worker.execute(() -> {
                try {
                    JSONObject credentials = new JSONObject();
                    credentials.put("email", enteredEmail);
                    credentials.put("password", enteredPassword);
                    JSONObject response = ApiClient.request(url, null, "POST", "/api/v1/mobile/login", credentials);
                    String token = response.getString("token");
                    storage.saveToken(token);
                    storage.setOrigin(url);
                    runOnUiThread(() -> {
                        loading = false;
                        refresh();
                    });
                } catch (Exception ex) {
                    runOnUiThread(() -> {
                        loading = false;
                        login.setEnabled(true);
                        login.setText("Prijavi se  →");
                        showError("Prijava nije uspjela: " + ex.getMessage());
                    });
                }
            });
        });
        TextView register = button("Nemate račun? Registrirajte se", false);
        margin(register, 0, 12, 0, 0);
        welcome.addView(register);
        register.setOnClickListener(v -> showRegistration());
        content.addView(welcome);
        TextView note = label("SANDBOX · Bez stvarnih uplata · Bez pohrane kartičnih podataka", 12, MUTED, false);
        note.setGravity(Gravity.CENTER);
        margin(note, 4, 20, 4, 0);
        content.addView(note);
    }
    /** Web and Android registration share one server-side Accounts service and identity store. */
    private void showRegistration() {
        snapshot = null;
        base();
        header(false);
        makeScroll();
        LinearLayout form = card(22);
        LinearLayout head = horizontal();
        addIcon(head, 46);
        head.addView(label("Brendigo Pay", 23, NAVY, true));
        form.addView(head);
        TextView title = label("Otvorite korisnički račun", 25, NAVY, true);
        margin(title, 0, 22, 0, 8);
        form.addView(title);
        TextView hint = label("Odaberite jedan od tri plaćena paketa. Registracija ne aktivira naplatu; operater mora potvrditi plaćanje.", 13, MUTED, false);
        margin(hint, 0, 0, 0, 18);
        form.addView(hint);

        form.addView(label("HTTPS adresa Brendigo Pay platforme", 13, NAVY, true));
        EditText server = field("https://brendigopay.com", false);
        server.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_URI);
        server.setText(storage.origin());
        margin(server, 0, 7, 0, 15);
        form.addView(server);

        form.addView(label("Naziv tvrtke ili trgovca", 13, NAVY, true));
        EditText name = field("Naziv trgovca", false);
        name.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_FLAG_CAP_WORDS);
        margin(name, 0, 7, 0, 15);
        form.addView(name);

        form.addView(label("Paket (plaća se nakon registracije)", 13, NAVY, true));
        Spinner tier = new Spinner(this);
        String[] tiers = {"Start · 19,90 € / mj · 1 domena",
                "Business · 49,90 € / mj · 5 domena",
                "Pro · 99,90 € / mj · 25 domena"};
        tier.setAdapter(new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, tiers));
        tier.setSelection(1);
        margin(tier, 0, 8, 0, 14);
        form.addView(tier);

        form.addView(label("Web-stranica (neobavezno)", 13, NAVY, true));
        EditText website = field("https://moja-trgovina.hr", false);
        website.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_URI);
        margin(website, 0, 7, 0, 15);
        form.addView(website);

        form.addView(label("E-mail adresa", 13, NAVY, true));
        EditText email = field("vas@email.hr", false);
        margin(email, 0, 7, 0, 15);
        form.addView(email);

        form.addView(label("Lozinka (najmanje 12 znakova)", 13, NAVY, true));
        EditText password = field("Nova lozinka", true);
        margin(password, 0, 7, 0, 15);
        form.addView(password);

        form.addView(label("Potvrdite lozinku", 13, NAVY, true));
        EditText confirmation = field("Ponovite lozinku", true);
        margin(confirmation, 0, 7, 0, 21);
        form.addView(confirmation);

        TextView register = button("Registriraj korisnički račun  →", true);
        form.addView(register);
        register.setOnClickListener(v -> {
            if (loading) return;
            String origin = ApiClient.origin(server.getText().toString());
            String selectedWebsite = website.getText().toString().trim();
            if (origin == null) { showError("Adresa platforme mora biti HTTPS domena."); return; }
            if (!selectedWebsite.isEmpty() && ApiClient.origin(selectedWebsite) == null) {
                showError("Adresa web-stranice mora biti HTTPS domena."); return;
            }
            if (name.getText().toString().trim().length() < 2
                    || email.getText().toString().trim().length() < 5
                    || password.getText().length() < 12
                    || !password.getText().toString().equals(confirmation.getText().toString())) {
                showError("Provjerite naziv, e-mail i podudaranje lozinki (12+ znakova).");
                return;
            }
            loading = true;
            register.setEnabled(false);
            register.setText("Registracija...");
            final String company = name.getText().toString().trim();
            final String address = email.getText().toString().trim();
            final String passphrase = password.getText().toString();
            final String requestedTier = new String[]{"start", "business", "pro"}[tier.getSelectedItemPosition()];
            worker.execute(() -> {
                try {
                    JSONObject body = new JSONObject();
                    body.put("name", company);
                    body.put("email", address);
                    body.put("password", passphrase);
                    body.put("requested_tier", requestedTier);
                    body.put("website", selectedWebsite);
                    JSONObject response = ApiClient.request(origin, null, "POST",
                            "/api/v1/mobile/register", body);
                    storage.saveToken(response.getString("token"));
                    storage.setOrigin(origin);
                    runOnUiThread(() -> {
                        loading = false;
                        showError("Račun je kreiran. Odabrani paket čeka provjeru uplate.");
                        selectedTab = "Pregled";
                        refresh();
                    });
                } catch (Exception ex) {
                    runOnUiThread(() -> {
                        loading = false;
                        register.setEnabled(true);
                        register.setText("Registriraj korisnički račun  →");
                        showError("Registracija nije uspjela: " + ex.getMessage());
                    });
                }
            });
        });

        TextView login = button("Već imate račun? Prijava", false);
        margin(login, 0, 12, 0, 0);
        form.addView(login);
        login.setOnClickListener(v -> showLogin(""));
        content.addView(form);
        TextView privacy = button("Pravila privatnosti i uvjeti usluge", false);
        margin(privacy, 4, 16, 4, 0);
        content.addView(privacy);
        privacy.setOnClickListener(v -> openUrl(storage.origin() + "/privacy"));
    }

    private void showError(String message) {
        Toast.makeText(this, message, Toast.LENGTH_LONG).show();
    }
    private void showLoading() {
        base();
        header(true);
        makeScroll();
        ProgressBar indicator = new ProgressBar(this);
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(dp(48), dp(48));
        p.gravity = Gravity.CENTER_HORIZONTAL;
        p.setMargins(0, dp(75), 0, dp(14));
        content.addView(indicator, p);
        TextView title = label("Sinkronizacija s web-panelom...", 16, NAVY, true);
        title.setGravity(Gravity.CENTER);
        content.addView(title);
    }
    private void refresh() {
        String token = storage.token();
        if (token == null) {
            showLogin("Sesija je istekla. Prijavite se ponovno.");
            return;
        }
        if (loading) return;
        loading = true;
        showLoading();
        worker.execute(() -> {
            try {
                JSONObject result = ApiClient.request(storage.origin(), token, "GET", "/api/v1/mobile/overview", null);
                runOnUiThread(() -> {
                    loading = false;
                    snapshot = result;
                    showDashboard();
                });
            } catch (Exception ex) {
                boolean unauthorized = ex instanceof ApiClient.HttpFailure && ((ApiClient.HttpFailure) ex).code == 401;
                if (unauthorized) storage.clearToken();
                runOnUiThread(() -> {
                    loading = false;
                    if (unauthorized) showLogin("Sesija je istekla ili je opozvana.");
                    else showRetry(ex.getMessage());
                });
            }
        });
    }
    private void showRetry(String reason) {
        base();
        header(true);
        makeScroll();
        LinearLayout box = card(22);
        box.addView(label("Nije moguće dohvatiti podatke", 21, NAVY, true));
        TextView msg = label(reason, 14, MUTED, false);
        margin(msg, 0, 13, 0, 18);
        box.addView(msg);
        TextView retry = button("Pokušaj ponovno", true);
        box.addView(retry);
        retry.setOnClickListener(v -> refresh());
        content.addView(box);
    }
    private void showDashboard() {
        if (snapshot == null) { refresh(); return; }
        base();
        header(true);
        makeScroll();
        if ("Pregled".equals(selectedTab)) overview();
        else if ("Transakcije".equals(selectedTab)) transactions();
        else if ("Linkovi".equals(selectedTab)) links();
        else sites();
        bottomNav();
    }
    private void bottomNav() {
        LinearLayout nav = horizontal();
        nav.setPadding(dp(5), dp(4), dp(5), dp(5));
        nav.setBackgroundColor(SIDEBAR);
        for (String tab : TABS) {
            boolean active = tab.equals(selectedTab);
            TextView item = label(tab, widthDp() < 360 ? 10 : 11,
                    active ? Color.WHITE : Color.rgb(195, 209, 244), active);
            item.setGravity(Gravity.CENTER);
            item.setMinHeight(dp(49));
            if (active) item.setBackground(surface(Color.rgb(44, 86, 231), 12, 0));
            nav.addView(item, new LinearLayout.LayoutParams(0, -1, 1));
            item.setOnClickListener(v -> { selectedTab = tab; showDashboard(); });
        }
        screen.addView(nav, new LinearLayout.LayoutParams(-1, dp(58)));
    }
    private void pageHeading(String title, String description) {
        TextView badge = label("BRENDIGO PAY  ·  TESTNO OKRUŽENJE", 10, BLUE, true);
        margin(badge, 2, 0, 0, 10);
        content.addView(badge);
        TextView name = label(title, 27, NAVY, true);
        margin(name, 1, 0, 0, 8);
        content.addView(name);
        TextView about = label(description, 14, MUTED, false);
        margin(about, 1, 0, 0, 18);
        content.addView(about);
    }
    private static JSONObject obj(JSONObject parent, String key) {
        JSONObject o = parent == null ? null : parent.optJSONObject(key);
        return o == null ? new JSONObject() : o;
    }
    private static JSONArray arr(JSONObject parent, String key) {
        JSONArray a = parent == null ? null : parent.optJSONArray(key);
        return a == null ? new JSONArray() : a;
    }
    private static String string(JSONObject o, String key, String fallback) {
        return o.isNull(key) ? fallback : o.optString(key, fallback);
    }
    private String money(long cents) {
        NumberFormat f = NumberFormat.getCurrencyInstance(new Locale("hr", "HR"));
        return f.format(BigDecimal.valueOf(cents, 2));
    }
    private void metric(String title, String value, String subtitle, boolean featured, LinearLayout row) {
        LinearLayout box = vertical();
        box.setPadding(dp(15), dp(15), dp(10), dp(15));
        box.setBackground(featured ? gradient(16) : surface(Color.WHITE, 16, STROKE));
        int foreground = featured ? Color.WHITE : NAVY;
        box.addView(label(title, 12, featured ? Color.rgb(232, 239, 255) : MUTED, true));
        TextView big = label(value, 21, foreground, true);
        margin(big, 0, 12, 0, 6);
        box.addView(big);
        box.addView(label(subtitle, 11, featured ? Color.rgb(225, 235, 255) : MUTED, false));
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(0, dp(118), 1);
        p.setMargins(dp(4), 0, dp(4), dp(10));
        row.addView(box, p);
    }
    private void overview() {
        JSONObject merchant = obj(snapshot, "merchant");
        JSONObject metrics = obj(snapshot, "metrics");
        JSONObject subscription = obj(snapshot, "subscription");
        pageHeading("Pregled", "Pozdrav, " + string(merchant, "name", "korisniče") + ". Vaši podaci sinkronizirani su s web-panelom.");
        LinearLayout first = horizontal();
        metric("Ukupna testna naplata", money(metrics.optLong("paid_minor", 0)), "Potvrđeni testni zapisi", true, first);
        metric("Uspješne uplate", String.valueOf(metrics.optInt("successful_payments", 0)), "U sandbox okruženju", false, first);
        content.addView(first);
        LinearLayout second = horizontal();
        metric("Aktivni linkovi", String.valueOf(metrics.optInt("active_links", 0)), "Za povezane domene", false, second);
        metric("Web-stranice", subscription.optInt("sites_used", 0) + " / " + subscription.optInt("site_limit", 0),
                string(subscription, "tier", "Bez paketa").toUpperCase(Locale.ROOT), false, second);
        content.addView(second);
        LinearLayout chart = card(18);
        chart.addView(label("Prihod · zadnjih 14 dana", 17, NAVY, true));
        TextView sub = label("Samo stvarni sandbox zapisi vašeg računa", 12, MUTED, false);
        margin(sub, 0, 8, 0, 12);
        chart.addView(sub);
        chart.addView(new RevenueChart(arr(snapshot, "transactions")), new LinearLayout.LayoutParams(-1, dp(195)));
        margin(chart, 4, 8, 4, 18);
        content.addView(chart);
        LinearLayout status = card(18);
        status.addView(label("Pretplata i licence", 17, NAVY, true));
        TextView state = label("Paket: " + string(subscription, "tier", "Nije aktiviran")
                + "  ·  " + (subscription.optString("status").equals("active") ? "Aktivan" : "Neaktivan"),
                14, NAVY, true);
        margin(state, 0, 10, 0, 8);
        status.addView(state);
        status.addView(label("Vrijedi do: " + string(subscription, "expires_at", "Čeka potvrdu naplate"), 12, MUTED, false));
        margin(status, 4, 0, 4, 18);
        content.addView(status);
        sectionTitle("Povezani pružatelji plaćanja");
        LinearLayout providers = card(17);
        JSONObject configured = obj(snapshot, "integrations");
        for (String provider : new String[]{"Stripe", "PayPal", "Revolut"}) {
            String key = provider.toLowerCase(Locale.ROOT);
            LinearLayout item = horizontal();
            item.setPadding(0, dp(8), 0, dp(8));
            TextView titleProvider = label(provider, 14, NAVY, true);
            item.addView(titleProvider, new LinearLayout.LayoutParams(0, dp(32), 1));
            boolean connected = configured.optBoolean(key, false);
            item.addView(chip(connected ? "Konfiguriran · test" : "Nije povezan",
                    connected ? "active" : "pending"));
            providers.addView(item);
        }
        TextView providerLink = button("Upravljaj pružateljima na webu  →", false);
        margin(providerLink, 0, 12, 0, 0);
        providers.addView(providerLink);
        providerLink.setOnClickListener(v -> openUrl(storage.origin() + "/settings"));
        margin(providers, 4, 0, 4, 17);
        content.addView(providers);
        sectionTitle("Nedavne transakcije");
        appendTransactions(arr(snapshot, "transactions"), 4);
        TextView more = button("Prikaži sve transakcije  →", false);
        margin(more, 4, 12, 4, 0);
        content.addView(more);
        more.setOnClickListener(v -> { selectedTab = "Transakcije"; showDashboard(); });
    }
    private void sectionTitle(String title) {
        TextView h = label(title, 18, NAVY, true);
        margin(h, 3, 3, 0, 15);
        content.addView(h);
    }
    private TextView chip(String text, String status) {
        int color = "paid".equals(status) || "active".equals(status) ? GREEN
                : "failed".equals(status) ? Color.rgb(185, 37, 54)
                : Color.rgb(155, 104, 20);
        TextView label = label(text, 11, color, true);
        label.setPadding(dp(9), dp(5), dp(9), dp(5));
        label.setBackground(surface("paid".equals(status) || "active".equals(status)
                ? Color.rgb(231, 248, 237) : "failed".equals(status)
                ? Color.rgb(255, 235, 237) : Color.rgb(255, 245, 222), 30, 0));
        return label;
    }
    private void appendTransactions(JSONArray transactions, int limit) {
        int count = Math.min(transactions.length(), limit);
        if (count == 0) { empty("Još nema transakcija. Nakon testnih uplata podaci će se automatski pojaviti."); return; }
        for (int i = 0; i < count; i++) {
            JSONObject txn = transactions.optJSONObject(i);
            if (txn == null) continue;
            LinearLayout box = card(15);
            LinearLayout row = horizontal();
            LinearLayout left = vertical();
            left.addView(label(string(txn, "description", "Transakcija"), 14, NAVY, true));
            TextView meta = label(string(txn, "created_at", "—").replace("T", " ").replace("+00:00", " UTC"),
                    11, MUTED, false);
            margin(meta, 0, 7, 0, 0);
            left.addView(meta);
            row.addView(left, new LinearLayout.LayoutParams(0, -2, 1));
            row.addView(label(money(txn.optLong("amount_minor", 0)), 15, NAVY, true));
            box.addView(row);
            String code = string(txn, "status", "pending");
            TextView status = chip("paid".equals(code) ? "✓ Uspješno" : "failed".equals(code) ? "✕ Neuspješno" : "◷ Na čekanju", code);
            LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(-2, -2);
            p.setMargins(0, dp(13), 0, 0);
            box.addView(status, p);
            margin(box, 4, 0, 4, 10);
            content.addView(box);
        }
    }
    private void transactions() {
        pageHeading("Transakcije", "Pregled stvarnih sandbox transakcija povezanih s vašim računom.");
        JSONObject metrics = obj(snapshot, "metrics");
        LinearLayout count = card(17);
        count.addView(label("Ukupno zapisa", 12, MUTED, true));
        count.addView(label(String.valueOf(metrics.optInt("transactions", 0)), 24, NAVY, true));
        margin(count, 4, 0, 4, 18);
        content.addView(count);
        appendTransactions(arr(snapshot, "transactions"), 50);
    }
    private void empty(String message) {
        LinearLayout box = card(18);
        TextView text = label(message, 14, MUTED, false);
        text.setGravity(Gravity.CENTER);
        box.addView(text);
        margin(box, 4, 0, 4, 12);
        content.addView(box);
    }
    private void links() {
        pageHeading("Platni linkovi", "Linkovi iz istog korisničkog računa kao na web-platformi i u WordPress dodatku.");
        JSONObject subscription = obj(snapshot, "subscription");
        if ("active".equals(subscription.optString("status")) && hasActiveSite()) {
            TextView create = button("+ Kreiraj platni link", true);
            margin(create, 4, 0, 4, 19);
            content.addView(create);
            create.setOnClickListener(v -> createLinkDialog());
        } else {
            empty("Za kreiranje linkova potreban je aktivan plaćeni paket i barem jedna aktivirana WordPress domena.");
        }
        JSONArray links = arr(snapshot, "links");
        if (links.length() == 0) { empty("Još nema platnih linkova."); return; }
        for (int i = 0; i < links.length(); i++) {
            JSONObject link = links.optJSONObject(i);
            if (link == null) continue;
            LinearLayout box = card(16);
            box.addView(label(string(link, "title", "Platni link"), 16, NAVY, true));
            TextView amount = label(money(link.optLong("amount_minor", 0)), 23, BLUE, true);
            margin(amount, 0, 9, 0, 12);
            box.addView(amount);
            box.addView(chip(link.optBoolean("active", false) ? "✓ Aktivan" : "Isključen",
                    link.optBoolean("active", false) ? "active" : "pending"));
            TextView open = button("Otvori poveznicu  →", false);
            margin(open, 0, 14, 0, 0);
            box.addView(open);
            open.setOnClickListener(v -> openUrl(link.optString("url", "")));
            LinearLayout actions = horizontal();
            TextView copy = button("Kopiraj link", false);
            TextView toggle = button(link.optBoolean("active", false) ? "Isključi" : "Uključi", false);
            LinearLayout.LayoutParams actionParams = new LinearLayout.LayoutParams(0, dp(48), 1);
            actionParams.setMargins(0, dp(9), dp(5), 0);
            actions.addView(copy, actionParams);
            LinearLayout.LayoutParams toggleParams = new LinearLayout.LayoutParams(0, dp(48), 1);
            toggleParams.setMargins(dp(5), dp(9), 0, 0);
            actions.addView(toggle, toggleParams);
            box.addView(actions);
            copy.setOnClickListener(v -> {
                ClipboardManager clipboard = (ClipboardManager) getSystemService(Context.CLIPBOARD_SERVICE);
                if (clipboard != null) {
                    clipboard.setPrimaryClip(ClipData.newPlainText("Brendigo Pay link", link.optString("url", "")));
                    showError("Platni link kopiran.");
                }
            });
            toggle.setOnClickListener(v -> toggleLink(link.optString("id", "")));
            margin(box, 4, 0, 4, 12);
            content.addView(box);
        }
    }
    private void toggleLink(String id) {
        if (!id.matches("[a-f0-9]{24}")) return;
        worker.execute(() -> {
            try {
                ApiClient.request(storage.origin(), storage.token(), "POST",
                        "/api/v1/mobile/links/" + id + "/toggle", new JSONObject());
                runOnUiThread(this::refresh);
            } catch (Exception e) {
                runOnUiThread(() -> showError(e.getMessage()));
            }
        });
    }

    private boolean hasActiveSite() {
        JSONArray sites = arr(snapshot, "sites");
        for (int i = 0; i < sites.length(); i++) {
            JSONObject site = sites.optJSONObject(i);
            if (site != null && "active".equals(site.optString("status"))) return true;
        }
        return false;
    }
    private void createLinkDialog() {
        JSONArray list = arr(snapshot, "sites");
        ArrayList<String> titles = new ArrayList<>();
        ArrayList<String> ids = new ArrayList<>();
        for (int i = 0; i < list.length(); i++) {
            JSONObject site = list.optJSONObject(i);
            if (site != null && "active".equals(site.optString("status"))) {
                titles.add(site.optString("origin"));
                ids.add(site.optString("id"));
            }
        }
        if (ids.isEmpty()) { showError("Najprije aktivirajte licencu web-stranice."); return; }
        LinearLayout form = vertical();
        form.setPadding(dp(18), dp(8), dp(18), 0);
        form.addView(label("Web-stranica", 13, NAVY, true));
        Spinner selector = new Spinner(this);
        ArrayAdapter<String> options = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, titles);
        selector.setAdapter(options);
        margin(selector, 0, 6, 0, 12);
        form.addView(selector);
        form.addView(label("Naziv proizvoda ili usluge", 13, NAVY, true));
        EditText title = field("npr. Online konzultacije", false);
        title.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_FLAG_CAP_SENTENCES);
        margin(title, 0, 6, 0, 12);
        form.addView(title);
        form.addView(label("Iznos EUR", 13, NAVY, true));
        EditText amount = field("49,90", false);
        amount.setInputType(InputType.TYPE_CLASS_NUMBER | InputType.TYPE_NUMBER_FLAG_DECIMAL);
        margin(amount, 0, 6, 0, 12);
        form.addView(amount);
        form.addView(label("Opis (neobavezno)", 13, NAVY, true));
        EditText description = field("Opis usluge", false);
        description.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_FLAG_CAP_SENTENCES);
        margin(description, 0, 6, 0, 8);
        form.addView(description);
        new AlertDialog.Builder(this)
                .setTitle("Novi testni platni link")
                .setView(form)
                .setNegativeButton("Odustani", null)
                .setPositiveButton("Kreiraj", (dialog, which) -> {
                    try {
                        JSONObject payload = new JSONObject();
                        payload.put("site_id", ids.get(selector.getSelectedItemPosition()));
                        payload.put("title", title.getText().toString());
                        payload.put("amount", amount.getText().toString().replace(',', '.'));
                        payload.put("description", description.getText().toString());
                        worker.execute(() -> {
                            try {
                                ApiClient.request(storage.origin(), storage.token(), "POST", "/api/v1/mobile/links", payload);
                                runOnUiThread(() -> { showError("Platni link je kreiran."); refresh(); });
                            } catch (Exception e) {
                                runOnUiThread(() -> showError(e.getMessage()));
                            }
                        });
                    } catch (Exception e) { showError("Neispravni podaci."); }
                }).show();
    }
    private void sites() {
        pageHeading("Web-stranice i licence", "Sve domene i licence vašeg plaćenog paketa sinkronizirane su s WordPressom.");
        JSONObject plan = obj(snapshot, "subscription");
        LinearLayout subscription = card(17);
        subscription.addView(label("Aktivni paket", 12, MUTED, true));
        TextView tier = label(string(plan, "tier", "Nema aktivnog paketa"), 22, BLUE, true);
        margin(tier, 0, 7, 0, 8);
        subscription.addView(tier);
        subscription.addView(label("Licencije: " + plan.optInt("sites_used", 0)
                + " / " + plan.optInt("site_limit", 0), 15, NAVY, true));
        margin(subscription, 4, 0, 4, 18);
        content.addView(subscription);
        JSONArray sites = arr(snapshot, "sites");
        if (sites.length() == 0) empty("Nemate povezanih domena. Registrirajte domenu i aktivirajte licencu na web-panelu.");
        for (int i = 0; i < sites.length(); i++) {
            JSONObject site = sites.optJSONObject(i);
            if (site == null) continue;
            LinearLayout box = card(16);
            box.addView(label(string(site, "origin", "Nepoznata domena"), 16, NAVY, true));
            TextView activated = chip("active".equals(site.optString("status")) ? "✓ Povezano" : "Čeka aktivaciju",
                    "active".equals(site.optString("status")) ? "active" : "pending");
            margin(activated, 0, 11, 0, 9);
            box.addView(activated);
            box.addView(label("Dodatak: " + string(site, "plugin_version", "Nije sinkronizirano"), 12, MUTED, false));
            TextView last = label("Zadnji kontakt: " + string(site, "last_seen_at", "Nikada"), 12, MUTED, false);
            margin(last, 0, 7, 0, 0);
            box.addView(last);
            margin(box, 4, 0, 4, 13);
            content.addView(box);
        }
        TextView portal = button("Upravljaj licencijama na webu  →", false);
        margin(portal, 4, 12, 4, 0);
        content.addView(portal);
        portal.setOnClickListener(v -> openUrl(storage.origin() + "/sites"));
    }
    private void showProfile() {
        if (snapshot == null) { showLogin(""); return; }
        JSONObject merchant = obj(snapshot, "merchant");
        LinearLayout form = vertical();
        form.setPadding(dp(22), dp(8), dp(22), dp(8));
        TextView name = label(string(merchant, "name", "Korisnik"), 19, NAVY, true);
        margin(name, 0, 0, 0, 8);
        form.addView(name);
        form.addView(label(string(merchant, "email", "—"), 14, MUTED, false));
        TextView origin = label("Platforma: " + storage.origin(), 13, BLUE, false);
        margin(origin, 0, 15, 0, 0);
        form.addView(origin);
        new AlertDialog.Builder(this)
                .setTitle("Brendigo Pay račun")
                .setView(form)
                .setNeutralButton("Web-panel", (d, w) -> openUrl(storage.origin() + "/dashboard"))
                .setNegativeButton("Zatvori", null)
                .setPositiveButton("Odjava", (d, w) -> logout()).show();
    }
    private void logout() {
        String old = storage.token();
        storage.clearToken();
        snapshot = null;
        selectedTab = "Pregled";
        showLogin("");
        if (old == null) return;
        worker.execute(() -> {
            try {
                ApiClient.request(storage.origin(), old, "POST", "/api/v1/mobile/logout", new JSONObject());
            } catch (Exception ignored) {
                runOnUiThread(() -> showError("Lokalna odjava završena. Nije potvrđeno udaljeno opozivanje tokena."));
            }
        });
    }
    private void openUrl(String raw) {
        try {
            Uri uri = Uri.parse(raw);
            if (!"https".equalsIgnoreCase(uri.getScheme()) || uri.getHost() == null) {
                showError("Dopuštene su samo HTTPS poveznice."); return;
            }
            startActivity(new Intent(Intent.ACTION_VIEW, uri));
        } catch (Exception e) { showError("Nije moguće otvoriti poveznicu."); }
    }

    /** Native Canvas chart; never draws fabricated revenue points. */
    private final class RevenueChart extends View {
        private final Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
        private final float[] amounts = new float[14];
        private float peak = 1;
        RevenueChart(JSONArray transactions) {
            super(MainActivity.this);
            setContentDescription("Grafikon stvarnog testnog prihoda u zadnjih 14 dana.");
            TimeZone utc = TimeZone.getTimeZone("UTC");
            Calendar current = Calendar.getInstance(utc);
            long today = current.getTimeInMillis() / 86400000L;
            SimpleDateFormat parser = new SimpleDateFormat("yyyy-MM-dd", Locale.US);
            parser.setTimeZone(utc);
            for (int i = 0; i < transactions.length(); i++) {
                JSONObject t = transactions.optJSONObject(i);
                if (t == null || !"paid".equals(t.optString("status"))) continue;
                String raw = t.optString("created_at", "");
                if (raw.length() < 10) continue;
                try {
                    Date date = parser.parse(raw.substring(0, 10));
                    if (date == null) continue;
                    long index = date.getTime() / 86400000L - today + 13;
                    if (index >= 0 && index < amounts.length) {
                        amounts[(int) index] += (float) t.optLong("amount_minor", 0) / 100f;
                        peak = Math.max(peak, amounts[(int) index]);
                    }
                } catch (Exception ignored) { /* Bad data is skipped, never plotted as a fake value. */ }
            }
        }
        @Override protected void onDraw(Canvas canvas) {
            super.onDraw(canvas);
            float left = dp(5), right = getWidth() - dp(5), top = dp(18), bottom = getHeight() - dp(22);
            float chartHeight = bottom - top;
            paint.setStyle(Paint.Style.STROKE);
            paint.setStrokeWidth(dp(1));
            paint.setColor(Color.rgb(226, 234, 247));
            for (int i = 0; i <= 3; i++) {
                float y = top + chartHeight * i / 3;
                canvas.drawLine(left, y, right, y, paint);
            }
            Path line = new Path();
            Path fill = new Path();
            float xStep = (right - left) / (amounts.length - 1);
            for (int i = 0; i < amounts.length; i++) {
                float x = left + i * xStep;
                float y = bottom - (amounts[i] / peak) * chartHeight * .88f;
                if (i == 0) { line.moveTo(x, y); fill.moveTo(x, y); }
                else { line.lineTo(x, y); fill.lineTo(x, y); }
            }
            fill.lineTo(right, bottom);
            fill.lineTo(left, bottom);
            fill.close();
            paint.setShader(new LinearGradient(0, top, 0, bottom,
                    Color.argb(90, 51, 94, 244), Color.argb(0, 51, 94, 244), Shader.TileMode.CLAMP));
            paint.setStyle(Paint.Style.FILL);
            canvas.drawPath(fill, paint);
            paint.setShader(null);
            paint.setColor(BLUE);
            paint.setStrokeWidth(dp(2.5f));
            paint.setStrokeJoin(Paint.Join.ROUND);
            paint.setStyle(Paint.Style.STROKE);
            canvas.drawPath(line, paint);
        }
    }
}
