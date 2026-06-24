<script setup>
    import logo from '@/assets/img/logo.jpg';

</script>
<template>
    <div id='receiptPopup' class="receiptPopup" >
        <div id="receiptbox">
            <div style="display: flex; gap: 10px;">
                <img :src="logo" style="width: 200px;height: fit-content;">
                <div>
                    <h2>Agenda Commercial LIMITED</h2>
                    <p>
                        <span>Website:</span><strong> www.agendgh.com</strong>
                        <span>E-mail:</span><strong>info@agendagh.com</strong>
                        <span>Postal Address:</span><strong>P.O. Box 1187 ACCRA</strong>
                        <span>Tel:</span><strong> +233 (0) 302 781 233</strong>
                        <span>Fax:</span><strong> +233 (0) 302 786 227</strong>
                    </p>
                    <p>
                        <span>Office Location:</span><strong> Ablemkpe Medical Supply Roads, Accra - Ghana</strong>
                    </p>
                </div>
            </div>
            
            <slot></slot>

            
        </div>
        
    </div>
</template>
<style scoped>
    .receiptPopup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 500;
        background-color: #00000091;
        overflow-y: auto;
        opacity: 0;
        z-index: -1;
        /* display: none; */

    }
    #receiptPopupcommands{
        position: fixed;
        right: 10px;
        top:10px;
        color: white;
        font-size: 30px;
        
    }
    #receiptPopupcommands i{
        display: block;
        margin-bottom: 10px;
        cursor:pointer;
    }
    #receiptbox{
        width: 816px;
        background-color: white;
        min-height: 1056px;
        color:black;
        margin:auto;
        padding:20px;
        display: flex;
        flex-direction: column;
        font-family: "Inter", sans-serif;
        letter-spacing: 0.01em;
    }
    #receiptbox > p{
        margin:5px;
    }
    #receiptbox > p span{
        display: inline-block;
        margin-right: 3px;
    }
    #receiptbox > p strong{
        display: inline-block;
        margin-right: 10px;
    }
</style>