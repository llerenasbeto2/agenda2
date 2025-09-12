<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";

const { props } = usePage();

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
  canRegister: {
    type: Boolean,
  },
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Iniciar sesión" />

    <!-- Mostrar mensajes flash
    <div
      v-if="props.flash && props.flash.alert"
      class="mb-4 font-medium text-sm text-yellow-600 bg-yellow-100 p-3 rounded-md"
    >
      {{ props.flash.alert }}
    </div>
    -->

    <div
      v-if="props.flash && props.flash.alert"
      class="flex items-start border-l-4 border-yellow-400 bg-yellow-100 p-4 rounded-md mb-4"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 text-yellow-400 mt-1 mr-3 flex-shrink-0"
        fill="currentColor"
        viewBox="0 0 24 24"
        stroke="none"
        stroke-width="2"
      >
        <path
          fill-rule="evenodd"
          d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
          clip-rule="evenodd"
        ></path>
      </svg>
      <div class="text-sm text-yellow-700">
        {{ props.flash.alert }} Por seguridad,
        <a
          href="/saml2/fb441f52-a65d-44f8-ac5b-bb12df182d90/logout"
          class="font-medium underline hover:text-yellow-600"
        >
          cierra sesión en la Federación de Identidades UCOL.
        </a>
      </div>
    </div>
    <!--
    <div
      v-if="props.flash && props.flash.error"
      class="mb-4 font-medium text-sm text-red-600 bg-red-100 p-3 rounded-md"
    >
      {{ props.flash.error }}
    </div>

-->

    <div
      v-if="props.flash && props.flash.error"
      class="flex items-start p-4 bg-red-50 border-l-4 border-red-400 rounded-md mb-4"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6 text-red-400 mr-3 flex-shrink-0"
        fill="currentColor"
        viewBox="0 0 24 24"
        stroke="none"
        stroke-width="2"
      >
        <path
          fill-rule="evenodd"
          d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z"
          clip-rule="evenodd"
        ></path>
      </svg>
      <div class="text-sm text-red-700">
        {{ props.flash.error }}
      </div>
    </div>

    <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
      {{ status }}
    </div>

    <form @submit.prevent="submit">
      <div>
        <InputLabel for="email" value="Correo electrónico" />

        <TextInput
          id="email"
          type="email"
          class="mt-1 block w-full"
          v-model="form.email"
          required
          autofocus
          autocomplete="username"
        />

        <InputError class="mt-2" :message="form.errors.email" />
      </div>

      <div class="mt-4">
        <InputLabel for="password" value="Contraseña" />

        <TextInput
          id="password"
          type="password"
          class="mt-1 block w-full"
          v-model="form.password"
          required
          autocomplete="current-password"
        />

        <InputError class="mt-2" :message="form.errors.password" />
      </div>

      <div class="flex justify-between items-center mt-4">
        <label class="flex items-center">
          <Checkbox name="remember" v-model:checked="form.remember" />
          <span class="ms-2 text-sm text-gray-600">Recuérdame</span>
        </label>

        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          ¿Olvidaste tu contraseña?
        </Link>
      </div>

      <div class="flex items-center justify-end mt-4">
        <PrimaryButton
          class="ms-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        >
          Iniciar sesión
        </PrimaryButton>
      </div>
    </form>

    <div class="flex items-center justify-center m-2">
      <div class="border-t border-gray-300 flex-grow"></div>
      <span class="mx-4 text-gray-500 text-sm whitespace-nowrap">O accede con:</span>
      <div class="border-t border-gray-300 flex-grow"></div>
    </div>

    <a
      href="/saml2/fb441f52-a65d-44f8-ac5b-bb12df182d90/login"
      class="uppercase items-center justify-center w-full inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 tracking-widest hover:bg-gray-100 focus:bg-gray-100 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
    >
      <img src="/images/logo-ucol.svg" alt="Universidad de Colima" class="w-28 ml-2" />
    </a>

    <div class="flex items-center justify-center mt-4">
      <span class="ms-2 text-sm text-gray-600">¿Necesitas una cuenta?&nbsp;</span>
      <Link
        v-if="canRegister"
        :href="route('register')"
        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        Regístrate
      </Link>
    </div>
  </GuestLayout>
</template>
