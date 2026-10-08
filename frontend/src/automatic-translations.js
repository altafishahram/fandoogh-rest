import { ref } from "vue";

export function useAutomaticTranslations(client) {
  const settings = ref({
    enabled: false,
    configured: false,
    provider: "google",
    daily_character_limit: 100000,
  });
  const status = ref(null),
    apiKey = ref(""),
    removeKey = ref(false),
    busy = ref(false),
    loading = ref(true),
    error = ref(""),
    notice = ref("");
  function acceptSettings(value) {
    settings.value = {
      enabled: !!value.enabled,
      configured: !!value.configured,
      provider: value.provider || "google",
      credential_source: value.credential_source || "none",
      daily_character_limit: Number(value.daily_character_limit) || 100000,
    };
  }
  function fail(exception, secret = "") {
    const message = exception?.message || "ارتباط با سرویس ترجمه ناموفق بود";
    error.value = secret ? message.split(secret).join("••••") : message;
  }
  async function refresh(initial = false) {
    if (initial) loading.value = true;
    try {
      if (initial) {
        const [configuration, progress] = await Promise.all([
          client.request("/manage/translation/settings"),
          client.request("/manage/translation/status"),
        ]);
        acceptSettings(configuration);
        status.value = progress;
      } else status.value = await client.request("/manage/translation/status");
      error.value = "";
    } catch (exception) {
      fail(exception);
    } finally {
      loading.value = false;
    }
  }
  async function save() {
    if (busy.value) return;
    busy.value = true;
    error.value = "";
    notice.value = "";
    const secret = apiKey.value.trim();
    const payload = {
      enabled: removeKey.value ? false : settings.value.enabled,
      daily_character_limit: Number(settings.value.daily_character_limit),
    };
    if (removeKey.value) payload.remove_key = true;
    else if (secret) payload.api_key = secret;
    try {
      acceptSettings(
        await client.request("/manage/translation/settings", "POST", payload),
      );
      removeKey.value = false;
      notice.value = "تنظیمات ترجمه ذخیره شد";
      await refresh();
    } catch (exception) {
      fail(exception, secret);
    } finally {
      apiKey.value = "";
      busy.value = false;
    }
  }
  async function action(kind) {
    if (busy.value) return;
    busy.value = true;
    error.value = "";
    notice.value = "";
    try {
      await client.request(`/manage/translation/${kind}`, "POST", {});
      notice.value =
        kind === "retry"
          ? "کارهای ناموفق برای تلاش دوباره در صف قرار گرفتند"
          : "محتوای موجود برای ترجمه در صف قرار گرفت";
      await refresh();
    } catch (exception) {
      fail(exception);
    } finally {
      busy.value = false;
    }
  }
  return {
    settings,
    status,
    apiKey,
    removeKey,
    busy,
    loading,
    error,
    notice,
    refresh,
    save,
    action,
  };
}
