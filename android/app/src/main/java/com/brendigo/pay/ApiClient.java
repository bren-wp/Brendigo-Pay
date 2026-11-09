package com.brendigo.pay;

import org.json.JSONObject;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import javax.net.ssl.HttpsURLConnection;

/** No redirects, cookies, plain HTTP, card-data collection, or third-party telemetry. */
final class ApiClient {
    static final int MAX_RESPONSE = 256 * 1024;
    private ApiClient() {}

    static String origin(String candidate) {
        if (candidate == null) return null;
        try {
            URI uri = new URI(candidate.trim());
            if (!"https".equalsIgnoreCase(uri.getScheme()) || uri.getHost() == null
                    || uri.getUserInfo() != null || uri.getQuery() != null || uri.getFragment() != null
                    || (uri.getPath() != null && !uri.getPath().isEmpty() && !"/".equals(uri.getPath()))
                    || uri.getPort() > 65535 || uri.getPort() == 0) return null;
            String host = uri.getHost().toLowerCase(java.util.Locale.ROOT);
            if (host.length() > 253 || host.contains("%")) return null;
            return "https://" + host + (uri.getPort() > 0 ? ":" + uri.getPort() : "");
        } catch (Exception ignored) { return null; }
    }

    static JSONObject request(String origin, String bearer, String method, String path, JSONObject payload)
            throws Exception {
        String base = origin(origin);
        if (base == null || !path.startsWith("/api/v1/mobile/") || path.contains("..")
                || !(method.equals("GET") || method.equals("POST"))) {
            throw new IOException("Neispravna HTTPS adresa platforme ili API put.");
        }
        URL url = new URL(base + path);
        HttpsURLConnection connection = (HttpsURLConnection) url.openConnection();
        connection.setConnectTimeout(12000);
        connection.setReadTimeout(15000);
        connection.setInstanceFollowRedirects(false);
        connection.setRequestMethod(method);
        connection.setRequestProperty("Accept", "application/json");
        connection.setRequestProperty("Cache-Control", "no-store");
        connection.setRequestProperty("User-Agent", "BrendigoPayAndroid/0.8.12");
        if (bearer != null && !bearer.isEmpty()) {
            connection.setRequestProperty("Authorization", "Bearer " + bearer);
        }
        if (payload != null) {
            connection.setDoOutput(true);
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8");
            byte[] bytes = payload.toString().getBytes(StandardCharsets.UTF_8);
            if (bytes.length > 65536) throw new IOException("Prevelik zahtjev.");
            connection.getOutputStream().write(bytes);
        }
        try {
            int code = connection.getResponseCode();
            if (code >= 300 && code <= 399) throw new HttpFailure(code, "Preusmjeravanje API zahtjeva nije dopušteno.");
            InputStream stream = code >= 400 ? connection.getErrorStream() : connection.getInputStream();
            if (stream == null) throw new HttpFailure(code, "Platforma nije vratila JSON odgovor.");
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            byte[] buffer = new byte[8192];
            int n;
            try (InputStream in = stream) {
                while ((n = in.read(buffer)) != -1) {
                    if (out.size() + n > MAX_RESPONSE) throw new IOException("Odgovor platforme je prevelik.");
                    out.write(buffer, 0, n);
                }
            }
            JSONObject json = new JSONObject(out.toString(StandardCharsets.UTF_8.name()));
            if (code < 200 || code >= 300) {
                String message = code == 401 ? "Prijava ili sesija nije valjana."
                        : code == 429 ? "Previše pokušaja. Pokušajte kasnije."
                        : code == 403 ? "Nemate dopuštenje za tu radnju."
                        : code == 409 ? "E-mail adresa već je registrirana."
                        : code == 422 ? "Provjerite unesene podatke."
                        : json.optString("error", "Zahtjev nije uspio.");
                throw new HttpFailure(code, message);
            }
            return json;
        } finally {
            connection.disconnect();
        }
    }

    static final class HttpFailure extends IOException {
        final int code;
        HttpFailure(int code, String message) { super(message); this.code = code; }
    }
}
