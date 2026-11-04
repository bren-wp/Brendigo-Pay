package com.brendigo.pay;

import android.content.Context;
import android.content.SharedPreferences;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;
import java.security.KeyStore;
import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;
import java.nio.charset.StandardCharsets;

/** AES-GCM key is non-exportable in Android Keystore; no token appears in logs or backups. */
final class SecureStore {
    private static final String ALIAS = "brendigo_pay_mobile_session_v1";
    private final SharedPreferences prefs;
    SecureStore(Context context) {
        prefs = context.getSharedPreferences("brendigo_local", Context.MODE_PRIVATE);
    }
    String origin() { return prefs.getString("origin", "https://brendigopay.com"); }
    void setOrigin(String origin) { prefs.edit().putString("origin", origin).apply(); }

    private SecretKey key() throws Exception {
        KeyStore store = KeyStore.getInstance("AndroidKeyStore");
        store.load(null);
        if (store.containsAlias(ALIAS)) return ((KeyStore.SecretKeyEntry) store.getEntry(ALIAS, null)).getSecretKey();
        KeyGenerator generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        generator.init(new KeyGenParameterSpec.Builder(ALIAS,
                KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setKeySize(256)
                .build());
        return generator.generateKey();
    }
    void saveToken(String token) throws Exception {
        Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
        cipher.init(Cipher.ENCRYPT_MODE, key());
        byte[] encrypted = cipher.doFinal(token.getBytes(StandardCharsets.UTF_8));
        prefs.edit().putString("token_iv", Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP))
                .putString("token", Base64.encodeToString(encrypted, Base64.NO_WRAP)).apply();
    }
    String token() {
        String value = prefs.getString("token", null);
        String iv = prefs.getString("token_iv", null);
        if (value == null || iv == null) return null;
        try {
            Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
            byte[] ivBytes = Base64.decode(iv, Base64.NO_WRAP);
            if (ivBytes.length != 12) throw new IllegalArgumentException("Invalid nonce");
            cipher.init(Cipher.DECRYPT_MODE, key(), new GCMParameterSpec(128, ivBytes));
            return new String(cipher.doFinal(Base64.decode(value, Base64.NO_WRAP)), StandardCharsets.UTF_8);
        } catch (Exception e) {
            clearToken();
            return null;
        }
    }
    void clearToken() { prefs.edit().remove("token").remove("token_iv").apply(); }
}
