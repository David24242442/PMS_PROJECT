<script setup>
    

</script>
<template>
    <div class="poppop" >
        <div id="">

            <slot></slot>

        </div>
        
    </div>
</template>
<style scoped>
    .poppop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 500;
        background-color: #00000091;
        padding: 50px 20%;
    }
    .deux{
        display: flex;
    }
    .deux > span{
        width: 50%;
        display: inline-block;
    }

    .popheader{
        height: 115px;
    }
    .popheader h3{
        margin:0;
        background-color: var(--deepcolor);
        text-align: center;
        padding:5px;
    }
    .popheader input{
        padding:7px;
        width: 100%;
        margin-top: 5px;
    }
    .popheader .deux{
        margin-top: 5px;
    }
    .popheader .deux span{
        background-color: var(--deepcolor);
        border-right: 2px solid var(--bglightcolor);
        text-align: center;
        padding:5px;
    }

    .poppopin {
        background-color: white;
        height: calc(100% - 50px);
    }

    .poppopclose {
        padding: 10px;
        cursor: pointer;
    }

    .poplist {
        padding: 10px;
        overflow: auto;
        max-height: calc( 100% - 115px);
    }
    .poplist .deux:nth-child(even){
        background-color: var(--bglightcolor)
    }
    .poplist .deux:hover{
        background-color: var(--deepcolor);
        cursor: pointer;
    }
    .poplist .deux span{
        padding:5px;
    }



    /* #receiptPopupcommands{
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
        font-family: Arial, sans-serif;
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
    } */
</style>