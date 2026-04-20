<template>
    <div>

        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg px-4 py-3 fixed-top" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); box-shadow: 0 2px 20px rgba(0,0,0,0.08);">
            <div class="container">
                <a class="navbar-brand fw-bold fs-4 text-primary" href="#">
                    🛒 KEFIS
                </a>
                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center gap-2">
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-dark" href="#home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-dark" href="#about">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-dark" href="#contact">Contact</a>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-primary px-4 fw-semibold" @click="goToLogin">
                                Login →
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="home" class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 50%, #1abc9c 100%); padding-top: 80px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 text-white" :class="{ 'slide-in-left': heroVisible }">
                        <div class="mb-3 animate-item" style="animation-delay: 0.1s">
                            <span class="badge bg-light text-primary px-3 py-2 rounded-pill fw-semibold">
                                🛒 Mini Supermarket Management
                            </span>
                        </div>
                        <h1 class="display-4 fw-bold mb-4 animate-item" style="line-height: 1.2; animation-delay: 0.2s">
                            Manage Your<br/>
                            <span style="color: #1abc9c;">Supermarket</span><br/>
                            With Ease
                        </h1>
                        <p class="lead mb-4 opacity-75 animate-item" style="animation-delay: 0.3s">
                            KEFIS is a powerful inventory management system designed
                            for multi-branch supermarkets. Track stock, record sales,
                            manage purchases and transfer stock between branches — all in one place.
                        </p>
                        <div class="d-flex gap-3 flex-wrap animate-item" style="animation-delay: 0.4s">
                            <button class="btn btn-light text-primary fw-bold px-4 py-2" @click="goToLogin">
                                Get Started →
                            </button>
                            <a href="#about" class="btn btn-outline-light fw-semibold px-4 py-2">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center mt-5 mt-lg-0">
                        <div class="row g-3">
                            <div class="col-6 animate-item" style="animation-delay: 0.2s">
                                <div class="card border-0 p-4 text-center h-100 hover-card" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px;">
                                    <div class="fs-1 mb-2">📦</div>
                                    <div class="text-white fw-bold fs-5">Stock</div>
                                    <div class="text-white opacity-75 small">Real-time tracking</div>
                                </div>
                            </div>
                            <div class="col-6 animate-item" style="animation-delay: 0.3s">
                                <div class="card border-0 p-4 text-center h-100 hover-card" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px;">
                                    <div class="fs-1 mb-2">💰</div>
                                    <div class="text-white fw-bold fs-5">Sales</div>
                                    <div class="text-white opacity-75 small">Record instantly</div>
                                </div>
                            </div>
                            <div class="col-6 animate-item" style="animation-delay: 0.4s">
                                <div class="card border-0 p-4 text-center h-100 hover-card" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px;">
                                    <div class="fs-1 mb-2">🔄</div>
                                    <div class="text-white fw-bold fs-5">Transfers</div>
                                    <div class="text-white opacity-75 small">Between branches</div>
                                </div>
                            </div>
                            <div class="col-6 animate-item" style="animation-delay: 0.5s">
                                <div class="card border-0 p-4 text-center h-100 hover-card" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 16px;">
                                    <div class="fs-1 mb-2">📊</div>
                                    <div class="text-white fw-bold fs-5">Reports</div>
                                    <div class="text-white opacity-75 small">Running balance</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="py-5 bg-white">
            <div class="container py-5">
                <div class="text-center mb-5 animate-on-scroll" ref="aboutTitle">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-3">
                        About Us
                    </span>
                    <h2 class="fw-bold display-6">Why Choose KEFIS?</h2>
                    <p class="text-muted lead">Built for modern supermarket management</p>
                </div>
                <div class="row g-4">
                    <div
                        v-for="(feature, index) in features"
                        :key="index"
                        class="col-md-4 animate-on-scroll"
                        :style="{ animationDelay: index * 0.1 + 's' }"
                    >
                        <div class="card border-0 shadow-sm h-100 p-4 text-center hover-card" style="border-radius: 16px;">
                            <div class="fs-1 mb-3">{{ feature.icon }}</div>
                            <h5 class="fw-bold">{{ feature.title }}</h5>
                            <p class="text-muted small">{{ feature.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-5" style="background: linear-gradient(135deg, #1e3a5f, #2d6a9f);">
            <div class="container py-4">
                <div class="row g-4 text-center text-white">
                    <div
                        v-for="(stat, index) in stats"
                        :key="index"
                        class="col-md-3 animate-on-scroll"
                        :style="{ animationDelay: index * 0.15 + 's' }"
                    >
                        <div class="stat-number fw-bold" style="font-size: 3rem;">{{ stat.value }}</div>
                        <div class="opacity-75">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-5 bg-light">
            <div class="container py-5">
                <div class="text-center mb-5 animate-on-scroll">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-3">
                        Contact Us
                    </span>
                    <h2 class="fw-bold display-6">Get In Touch</h2>
                    <p class="text-muted lead">We are here to help you</p>
                </div>
                <div class="row g-4 justify-content-center">
                    <div
                        v-for="(contact, index) in contacts"
                        :key="index"
                        class="col-md-4 text-center animate-on-scroll"
                        :style="{ animationDelay: index * 0.15 + 's' }"
                    >
                        <div class="card border-0 shadow-sm p-4 hover-card" style="border-radius: 16px;">
                            <div class="fs-1 mb-3">{{ contact.icon }}</div>
                            <h6 class="fw-bold">{{ contact.title }}</h6>
                            <p class="text-muted small mb-0">{{ contact.value }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-4 text-center text-white" style="background: #1e3a5f;">
            <div class="container">
                <p class="mb-0 small opacity-75">© 2026 KEFIS Mini Supermarket. All rights reserved.</p>
            </div>
        </footer>

    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router    = useRouter()
const authStore = useAuthStore()
const heroVisible = ref(false)

const features = [
    { icon: '🏢', title: 'Multi-Branch Support',    desc: 'Manage multiple branches from one system. Each branch has its own stock, sales and purchase records.' },
    { icon: '👤', title: 'Role Based Access',        desc: 'Managers see everything. Shopkeepers only see their branch. Secure and controlled access for every user.' },
    { icon: '📈', title: 'Stock Reports',            desc: 'Get detailed stock movement reports with running balances. Filter by branch and SKU for precise insights.' },
    { icon: '🔄', title: 'Inter-Branch Transfers',   desc: 'Move stock between branches seamlessly. Stock balances update automatically in real time.' },
    { icon: '🛒', title: 'Purchase Management',      desc: 'Record purchases from suppliers. Stock is updated automatically when a purchase is recorded.' },
    { icon: '💰', title: 'Sales Tracking',           desc: 'Record every sale with customer details. Stock deducts automatically and history is always available.' },
]

const stats = [
    { value: '2',   label: 'Branches' },
    { value: '5',   label: 'Products / SKUs' },
    { value: '3',   label: 'Users' },
    { value: '24/7', label: 'Access' },
]

const contacts = [
    { icon: '📍', title: 'Head Office', value: 'Nairobi CBD, Kenya' },
    { icon: '📞', title: 'Phone',       value: '+254 700 000 001' },
    { icon: '📧', title: 'Email',       value: 'info@kefis.com' },
]

const goToLogin = () => {
    if (authStore.isLoggedIn) {
        router.push('/dashboard')
    } else {
        router.push('/login')
    }
}

onMounted(() => {
    // Hero animation
    setTimeout(() => {
        heroVisible.value = true
    }, 100)

    // Scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible')
            }
        })
    }, { threshold: 0.15 })

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el)
    })
})
</script>

<style scoped>
/* Hero animations */
.animate-item {
    opacity: 0;
    transform: translateY(30px);
    animation: slideUp 0.6s ease forwards;
}

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scroll animations */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.animate-on-scroll.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Hover cards */
.hover-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: default;
}

.hover-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}

/* Stat counter pulse */
.stat-number {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.05); }
}

/* Navbar link hover */
.nav-link {
    position: relative;
    transition: color 0.3s ease;
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: #0d6efd;
    transition: width 0.3s ease;
}

.nav-link:hover::after {
    width: 100%;
}
</style>