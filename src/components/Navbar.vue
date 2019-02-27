<template>
  <!-- Image and text -->
    <nav class="navbar navbar-light bg-light">
        <span class="navbar-brand" href="#">
            <img src="/docs/4.3/assets/brand/bootstrap-solid.svg" width="30" height="30" class="d-inline-block align-top" alt="">
            Einkaufliste
        </span>
        <button class="btn btn-light navbar-toggler" :class="{'active':show}" @click="navbarToggler">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-right" :class="{'show':show}">
            <button class="dropdown-item"  @click="navbarToggler">Refresh</button>
            <button class="dropdown-item"  @click="navbarToggler">Delete All</button>
        </div>
    </nav>
</template>

<script>
export default {
    data(){
        return{
            show: false
        }
    },
    methods:{
        navbarToggler(){
            this.show = !this.show
        }
    }
}
</script>

