<script setup>
defineProps({
  code: { type: String, required: true },
  label: { type: String, default: "" },
});
</script>
<template>
  <svg
    viewBox="0 0 60 40"
    class="ac-country-flag"
    role="img"
    :aria-label="label"
    focusable="false"
  >
    <template v-if="code === 'fa'">
      <path fill="#fff" d="M0 0h60v40H0z" />
      <path fill="#239f40" d="M0 0h60v13.33H0z" />
      <path fill="#da0000" d="M0 26.67h60V40H0z" />
      <g fill="none" stroke="#da0000" stroke-width="1.7">
        <path
          d="M27 16c-4 6-2 10 3 10s7-4 3-10M30 15v12M25 20c0 3 2 5 5 5s5-2 5-5"
        />
        <path d="m28 15 2-2 2 2" />
      </g>
    </template>
    <template v-else-if="code === 'en'">
      <path fill="#fff" d="M0 0h60v40H0z" />
      <path
        v-for="i in 7"
        :key="i"
        fill="#b22234"
        :d="`M0 ${((i - 1) * 40) / 6.5}h60v${40 / 13}H0z`"
      />
      <path fill="#3c3b6e" d="M0 0h27v21.5H0z" />
      <g fill="#fff">
        <g v-for="row in 9" :key="row">
          <path
            v-for="column in row % 2 ? 6 : 5"
            :key="column"
            :transform="`translate(${(row % 2 ? 2.25 : 4.5) + (column - 1) * 4.5} ${2.15 + (row - 1) * 2.15})`"
            d="M0-1 .224-.309 .951-.309 .363.118 .588.809 0 .382-.588.809-.363.118-.951-.309-.224-.309Z"
          />
        </g>
      </g>
    </template>
    <template v-else-if="code === 'zh'">
      <path fill="#de2910" d="M0 0h60v40H0z" />
      <path
        fill="#ffde00"
        d="m10 5 1.8 5.5h5.8l-4.7 3.4 1.8 5.5-4.7-3.4-4.7 3.4 1.8-5.5L2.4 10.5h5.8z"
      />
      <g fill="#ffde00">
        <path
          v-for="(p, i) in [
            [21, 5],
            [25, 10],
            [25, 17],
            [21, 22],
          ]"
          :key="i"
          :transform="`translate(${p[0]} ${p[1]}) scale(.34) rotate(${i * 22})`"
          d="m0-5 1.2 3.5h3.7L2 .7l1.1 3.5L0 2l-3.1 2.2L-2 .7l-2.9-2.2h3.7z"
        />
      </g>
    </template>
    <template v-else-if="code === 'tr'">
      <path fill="#e30a17" d="M0 0h60v40H0z" />
      <circle cx="25" cy="20" r="11" fill="#fff" />
      <circle cx="28" cy="20" r="9" fill="#e30a17" />
      <path
        fill="#fff"
        d="m38 13 1.6 4.8h5l-4 2.9 1.5 4.8-4.1-3-4.1 3 1.6-4.8-4-2.9h5z"
      />
    </template>
  </svg>
</template>
