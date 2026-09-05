<script setup>
    import Nav from '@/components/Nav.vue'
    import LoadingOverlay from '@/components/LoadingOverlay.vue'
    import { RouterLink, RouterView } from 'vue-router'
    import { ref } from 'vue'
    import 'bootstrap-icons/font/bootstrap-icons.css'
    import { useUsersStore } from '@/stores/user';
    
    const userstore = useUsersStore()
    let { loguser } = userstore

    const isNavExpanded = ref(false);

    const handleHoverChange = (expanded) => {
        isNavExpanded.value = expanded;
    };
</script>

<template>

    <div id='mainDiv'>
        <LoadingOverlay />
        <Nav @hover-change="handleHoverChange"></Nav>
        <div
            id="mainview"
            :class="{ 'expanded-view': isNavExpanded }"
        >
            <div style="padding:24px">
                <RouterView></RouterView>
            </div>
        </div>
    </div>
    <!-- ERP -->
</template>

<style>
    @media print {
        nav{
            display: none !important;
        }
        #mainview{
            left:0px !important;
            width: 100% !important;
        }
        #topheader{
            display: none !important;
        }
    }
    *{
        box-sizing: border-box;
    }
    html, body, #app{
        height: 100%;
        margin: 0;
    }
    #mainDiv{
        display: flex;
        height: 100%;
    }
    #homelink{
        text-decoration: none;
        color: var(--prof-primary); /* Navy */
        font-weight: 900;
        font-size: 1.1rem;
        letter-spacing: -0.5px;
    }
    #mainview {
        padding: 0;
        background: var(--prof-bg);
        position: absolute;
        top: 0;
        bottom: 0;
        overflow-y: auto;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        left: 80px;
        width: calc(100% - 80px);
    }

    #mainview.expanded-view {
        left: 260px;
        width: calc(100% - 260px);
    }
    
</style>