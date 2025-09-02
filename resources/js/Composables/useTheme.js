import { ref, onMounted, watch } from 'vue'

export function useTheme() {
    const isDark = ref(false)

    const applyTheme = (theme) => {
        const htmlEl = document.documentElement
        
        if (theme === 'dark') {
            htmlEl.classList.add('dark')
            htmlEl.classList.remove('light')
            isDark.value = true
        } else {
            htmlEl.classList.add('light')
            htmlEl.classList.remove('dark')
            isDark.value = false
        }
    }

    const toggleTheme = () => {
        const newTheme = isDark.value ? 'light' : 'dark'
        applyTheme(newTheme)
        localStorage.setItem('theme', newTheme)
    }

    const initializeTheme = () => {
        const savedTheme = localStorage.getItem('theme')
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

        if (savedTheme) {
            applyTheme(savedTheme)
        } else if (prefersDark) {
            applyTheme('dark')
        } else {
            applyTheme('light')
        }
    }

    onMounted(() => {
        initializeTheme()
    })

    return {
        isDark,
        toggleTheme,
        applyTheme,
        initializeTheme
    }
}