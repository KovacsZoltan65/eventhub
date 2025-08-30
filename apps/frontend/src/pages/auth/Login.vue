<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '@/services/AuthService';

//const email = ref('');
//const password = ref('');

// ADMIN belépés
const email = ref('admin@eventhub.local');
const password = ref('Admin123!');

// ORGANIZER belépés
//const email = ref('org1@eventhub.local');
//const password = ref('Org123!');
//const email = ref('org2@eventhub.local');
//const password = ref('Org123!');

// USER belépés
//const email = ref('user@eventhub.local');
//const password = ref('User123!');

const loading = ref(false);
const router = useRouter();

/**
 * Bejelentkezési űrlap beküldési kezelője.
 * Bejelentkezik a felhasználóba, és átirányítja az irányítópultra.
 * @async
 */
const submit = async () => {
    
    if (loading.value) return;

    loading.value = true;

    try {
        // Bejelentkezés e-mail címmel és jelszóval
        await AuthService.login({
            email: email.value, 
            password: password.value 
        });
        // Átirányítás az irányítópultra
        router.push('/'); // vagy szervező/admin szerint
    } catch (e) {
        // Opcionális: toast vagy <small class="contrast"> hibaüzenet
    } finally {
        // A betöltési állapot visszaállítása
        loading.value = false;
    }
}
</script>

<template>
    <main class="container">
        <!-- fejléc (opcionális) -->
        <nav style="display:flex; justify-content:space-between; align-items:center; padding-block:0.75rem;">
            <ul><li><strong>EventHub</strong></li></ul>
            <ul>
                <li><a href="/">Események</a></li>
            </ul>
        </nav>

        <section class="auth-wrap">
            <article class="auth-card">
            <header>
                <h1 style="margin:0 0 .25rem;">Bejelentkezés</h1>
                <p class="secondary">Lépj be az adminisztrációhoz vagy a szervezői felülethez.</p>
            </header>

            <form @submit.prevent="submit" class="auth-actions">
                <!-- EMAIL -->
                <div>
                    <label for="email">Email</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        name="email"
                        placeholder="admin@eventhub.local"
                        autocomplete="email"
                        required
                    />
                </div>

                <!-- JELSZÓ -->
                <div>
                    <label for="password">Jelszó</label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    />
                </div>

                <button :aria-busy="loading" :disabled="loading" type="submit">
                    {{ loading ? 'Beléptetés…' : 'Belépés' }}
                </button>
            </form>


            <!-- opcionális: kis lábjegyzet -->
            <footer style="margin-top: .75rem;">
                <small class="secondary">
                    Tipp: <kbd>Enter</kbd> megnyomásával is bejelentkezhetsz.

                    - admin: admin@eventhub.local | Admin123!<br/>
                    - organizer1: org1@eventhub.local | Org123!<br/>
                    - organizer2: org2@eventhub.local | Org123!<br/>
                    - user: user@eventhub.local | User123!
                </small>
            </footer>
      </article>
    </section>
  </main>
</template>
