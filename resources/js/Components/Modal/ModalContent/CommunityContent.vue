// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Modal/ModalContent/CommunityContent.vue
<template>
    <div class="p-6 rounded-lg shadow-lg bg-sky-200 bg-opacity-80">
        <h2 class="text-xl font-bold whitespace-nowrap mb-4">社群</h2>
        <ul class="space-y-4">
            <li v-for="group in groups" :key="group.id" class="flex items-center justify-between space-x-4">
                <!-- 群号 -->
                <span class="text-zinc-950 font-bold whitespace-nowrap">{{ group.name }}: {{ group.number }}</span>
                <!-- QQ 加群按钮 -->
                <a :href="group.link" target="_blank" class="flex-shrink-0">
                    <img border="0" :src="group.imgSrc" :alt="group.alt" :title="group.title">
                </a>
            </li>
        </ul>
    </div>
</template>

<script setup>
const groups = [
    {
        id: 1,
        name: '官方群',
        number: '736250242',
        link: 'https://qm.qq.com/cgi-bin/qm/qr?k=2LWqL9kEm4RCkBfmR7amYbXiFFqsXT_1&jump_from=webapi&authKey=cW8TXI2GV09Y6ThMrVIKTlNc3M/vqnR8dJe0jKsa2wPITtrVMkqCVO4UdQTXcFjT',
        imgSrc: '//pub.idqqimg.com/wpa/images/group.png',
        alt: 'Solarmax3官方群',
        title: 'Solarmax3官方群'
    },
    {
        id: 2,
        name: '官方二群',
        number: '1050145891',
        link: 'https://qm.qq.com/cgi-bin/qm/qr?k=c03YJdaeRp_vt5kLMHE3blBU0AFNC229&jump_from=webapi&authKey=eZFY2Bj9dDmFzY3g4EcrWLYJImwzkYshNX2i4oJO4ykRHrWmbnER/tjbxeidy7Sy',
        imgSrc: '//pub.idqqimg.com/wpa/images/group.png',
        alt: 'Solarmax3官方群2群',
        title: 'Solarmax3官方群2群'
    },
    {
        id: 3,
        name: '官方三群',
        number: '100027488',
        link: 'https://qm.qq.com/cgi-bin/qm/qr?k=-JwoTrXvEfq5-Y9m4QBjK8oA5nFmZuaw&jump_from=webapi&authKey=/icy1FKgNZ7PcQbyqW9JlsgGzcRcG/T5/nt7sQgELRWFKyd5guZojsAvv+XrPcsn',
        imgSrc: '//pub.idqqimg.com/wpa/images/group.png',
        alt: 'Solarmax3官方群3群',
        title: 'Solarmax3官方群3群'
    },
    {
        id: 4,
        name: '自制总群',
        number: '145820245',
        link: 'https://qm.qq.com/cgi-bin/qm/qr?k=pq1UZ2v341izbq7YNnfoi-Bln56ijiE9&jump_from=webapi&authKey=okwwpDvumG0ra+tApNZpwleNAZ3wIFC4r+SConqxjCCZVEdQ/JO7VOzRokHV7YwM',
        imgSrc: '//pub.idqqimg.com/wpa/images/group.png',
        alt: 'Solarmax自制总群',
        title: 'Solarmax自制总群'
    }
]
</script>
