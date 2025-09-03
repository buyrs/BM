import { ref, computed } from 'vue'

// Simple theme composable
const isDarkMode = ref(false)

export function useTheme() {
    const isDark = computed(() => isDarkMode.value)
    
    const toggleTheme = () => {
        isDarkMode.value = !isDarkMode.value
        // Update document class for dark mode
        if (isDarkMode.value) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }
    
    const setTheme = (dark) => {
        isDarkMode.value = dark
        if (dark) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }
    
    // Initialize theme from localStorage or system preference
    const initTheme = () => {
        const savedTheme = localStorage.getItem('theme')
        if (savedTheme) {
            setTheme(savedTheme === 'dark')
        } else {
            // Use system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            setTheme(prefersDark)
        }
    }
    
    return {
        isDark,
        toggleTheme,
        setTheme,
        initTheme
    }
}