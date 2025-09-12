<script setup>
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
import { Head } from '@inertiajs/vue3';
import NavLink from '@/Components/NavLink.vue';   

const addGlow = (event) => {
    const element = event.target;
    element.classList.add('glow');
    setTimeout(() => {
        element.classList.remove('glow');
    }, 6000); // El brillo durará 1 segundo
};
</script>

<style scoped>
.glow {
    box-shadow: 0 0 10px 5px rgba(255, 255, 255, 0.8);
    transition: box-shadow 0.5s ease-in-out;
}
</style>

<template>
    <Head title="Entrenamiento" />

    <DynamicLayout>
        <div
            style="
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                background-image: url('https://i.ytimg.com/vi/GBuO4EOx558/maxresdefault.jpg');
                background-size: cover;
                background-position: center;
                text-align: center;
                color: white;
                text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.8);
            "
        >
            <div>
                <h1 style="font-size: 3rem; font-family: Arial, sans-serif; margin-bottom: 20px;">
                    ¡Bienvenido al Entrenamiento!
                </h1>
                <p style="font-size: 1.5rem; font-family: Arial, sans-serif;">
                    Aquí puedes practicar y aprender más sobre Laravel Sail.
                </p>
                <NavLink href="/dashboard" 
                style="
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    font-size: 1.2rem;
                    color: #fff;
                    background-color: rgba(0, 0, 0, 0.7);
                    border-radius: 5px;
                    text-decoration: none;;"
                    @click="addGlow"
                >
                    Volver al Dashboard
                </NavLink>
            </div>
        </div>
    </DynamicLayout>
</template>

<script>
export default {
    name: "Entrenamiento",
};
</script>
