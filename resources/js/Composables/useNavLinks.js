import { ref, onMounted } from 'vue';

export function useNavLinks() {
    const navLinks = ref([
        { href: "/text", text: "首页", active: false },
        { href: "/text2", text: "游戏维基", active: false },
        { href: "#contact", text: "Contact", active: false },
    ]);

    const setActiveNavLink = () => {
        const currentPath = window.location.pathname;
        navLinks.value.forEach(link => {
            link.active = link.href === currentPath;
        });
    };

    const handleNavClick = (href) => {
        navLinks.value.forEach(link => {
            link.active = link.href === href;
        });
        window.location.href = href; // 页面跳转
    };

    onMounted(() => {
        setActiveNavLink();
    });

    return { navLinks, handleNavClick };
}
