/** One shared request per language; an older response can never replace the chosen language. */
export async function loadCustomerBootstrap(state, code, fetchBootstrap) {
  if (state.loadingLanguage === code && state.languagePromise)
    return state.languagePromise;
  const generation = ++state.languageRequest;
  state.loadingLanguage = code;
  state.bootstrapLoading = true;
  state.bootstrapError = "";
  const promise = fetchBootstrap(code)
    .then((response) => {
      if (generation !== state.languageRequest || state.language !== code)
        return null;
      state.bootstrap = response;
      state.bootstrap.language = response.language || code;
      if (response.language && response.language !== code)
        state.language = response.language;
      return response;
    })
    .catch((error) => {
      if (generation === state.languageRequest)
        state.bootstrapError =
          error instanceof TypeError
            ? "ارتباط با سرور ناموفق بود"
            : error.message;
      return null;
    })
    .finally(() => {
      if (generation === state.languageRequest) {
        state.bootstrapLoading = false;
        state.languagePromise = null;
      }
    });
  state.languagePromise = promise;
  return promise;
}
