<template>
  <div>
    <div class="parent" :class="{'modal-shrink':showMenu}">
      <div class="top">
        <div class="title">Einkaufliste</div>
        <div class="input">
          <input type="text" placeholder="z.B. Milch"  v-model="newItemInput" @keyup.enter="addItem">
          <button @click="syncData" v-if="showSync"><i class="fas fa-sync" :class="{'synchronizing':statusSyncData}"></i></button> 
          <button @click="addItem" v-else><i class="fas fa-plus"></i></button>
          <button @click="showMenu = !showMenu"><i class="fas fa-ellipsis-v"></i></button>    
        </div>
      </div>
      <div class="liste">
        <transition-group name="main-list" tag="ul">>
          <li v-for="(item, index) in items" @click="checkItem(index)" @dblclick="removeItem(index)" :key="item.timestamp" :class="{'checked':items[index].status=='checked'}"> {{ item.item }} - ( {{items[index].quantity}} ) </li>
        </transition-group>
      </div>
      <div>
        {{newItemInput}}  
      </div>      
    </div>
    <transition name="slide">
      <div class="modal" @click="modal" v-if="showMenu">
        <div class="modal-elemente">
          <div @click="showMenu = !showMenu"><span><i class="fas fa-times"></i></span></div>
          <div><span>Neue Liste <i class="fas fa-file-medical"></i></span></div>
          <div><span @click="emptyList">Liste Leeren <i class="far fa-file"></i></span></div>
          <div><span @click="removeChecked">Gekaufte Löschen <i class="fas fa-trash-alt"></i></span></div> 
          <div><span @click="checkedToBottom">Gekauft nach unten <i class="fas fa-file-download"></i></span></div>          
          <div><span>Einkaufliste Teilen <i class="fas fa-file-export"></i></span></div>
          <div><span>Login <i class="fas fa-user"></i></span></div>
          <div><span>Hilfe <i class="fas fa-info-circle"></i></span></div>
        </div>      
      </div>
    </transition>
  </div>
      
</template>

<script>
export default {
  data(){
    return{
      listID:'',
      items:[
        {
          item: 'Milch',
          quantity: 1,
          timestamp: '1',
          userID:'',
          status: 'unchecked'
        },
        {
          item: 'Brot',
          quantity: 2,
          timestamp: '2',
          userID:'',
          status: 'unchecked'
        },
        {
          item: 'Zucker',
          quantity: 1,
          timestamp: '3',
          userID:'',
          status: 'unchecked'
        }
      ],      
      newItemInput:'',
      showMenu:false,
      showSync: true,
      statusSyncData: false,
      clicked: 0,
      itemIndexTmp: ''
    }
  },
  created(){
    if(localStorage.getItem('items')!=null){
      this.items=JSON.parse(localStorage.getItem('items'))
    }
  },
  methods:{
    syncData(){
      this.listID = this.makeUniqueID()
      history.pushState({}, '', '?'+this.listID)
      let vm = this
      this.statusSyncData = true
      setTimeout(() => {
        this.statusSyncData = false
      },1200)
    },
    syncDataLocal(){
      localStorage.setItem('items', JSON.stringify(this.items))
    },
    removeDataLocal(){
      localStorage.removeItem('items');
    },
    removeItem(index){      
      if(this.items[index].status == 'checked'){
        this.items.splice(index, 1)
        this.syncDataLocal()
        return
      }
    },
    checkItem(index){
      if (this.items[index].status == 'unchecked'){
        this.items[index].status = 'checked'     
      }  else {
        this.items[index].status = 'unchecked'       
      }
      
      this.items[index].timestamp = new Date().toJSON() 
      this.syncDataLocal()

      let vm=this
      vm.clicked++
      if(vm.clicked==1){
        vm.itemIndexTmp = index
        setTimeout(()=>{
          vm.clicked=0
        },300)
      }else{
        vm.clicked=0
        if(index == vm.itemIndexTmp){
          vm.itemIndexTmp = ''
          vm.removeItem(index)
        }
        
      }           
    },
    addItem(){
      if(this.newItemInput == ''){ return }
      let newItem={
        item:'',
        quantity: 1,
        timestamp:'',
        userID:'',
        status: 'unchecked'
      }
      newItem.item = this.newItemInput
      newItem.timestamp= new Date().toJSON()
      this.items.unshift(newItem)
      this.newItemInput = ''  
      this.syncDataLocal()
    },
    emptyList(){
      this.removeDataLocal()
      this.items=[]
      this.showMenu=false  
    },
    removeChecked(){
      this.checkedToBottom()
      let firstCheckedIndex = this.items.findIndex(firstChecked)
      if (firstCheckedIndex != -1){
        this.items.splice(firstCheckedIndex, this.items.length)
      }
      function firstChecked(object){
        return object.status == 'checked'
      }
      this.syncDataLocal()
      this.showMenu=false
    },
    checkedToBottom(){
      this.items.sort(compareStatus)
      function compareStatus(a,b){
        if (a.status < b.status)
          return 1;
        if (a.status > b.status)
          return -1;
        return 0;
      }
      this.syncDataLocal()
      this.showMenu=false
    },
    shuffle(){
      this.items = _.shuffle(this.items)
    },
    modal(e){
      if(e.target.className == 'modal'){
        this.showMenu = !this.showMenu
      }      
    },
    makeUniqueID(){
      let text = "";
      let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

      for (let i = 0; i < 8; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));

      return text + new Date().getTime().toString(36);
    }   
  },
  watch:{
    newItemInput: function(val) {
      if (val == '') {
        this.showSync = true
      } else {
        this.showSync = false
      } 
    }
  }
}
</script>


<style>

.checked{
  color: #b5b5b5;
}
.checked:after {
   content:  "\2713 ";
}
.main-list-move {
  transition: transform .5s;
}
.main-list-item {
  transition: all .5s;
  display: inline-block;
  margin-right: 10px;
}
.main-list-enter-active, .main-list-leave-active {
  transition: all .5s;
}
.main-list-enter{
  opacity: 0;
  transform: translateY(-50px);
}
.main-list-leave-to {
  opacity: 0;
  transform: scale(0);
}
.main-list-leave-active {
  position: absolute;
}

html, body{
  font-family: 'Gloria Hallelujah', cursive;
  margin: 0;
  background: rgb(239, 239, 239);
  color:#6b6b6b;
  font-size: large;
}
.top{
  margin-bottom: 20px;
}
.title{
  padding: 10px 0 10px;
  text-align: center;
  font-size: xx-large;
  color: #c5c5c5;
}
.input, .liste{
  display: flex;
  justify-content: center;
}
input{
  height: 35px;
  max-width: 400px;
  width: 50%;
  font-size: large;
  font-family: inherit;
  padding: 5px 10px 5px 30px;  
  margin-right: 10px;
  border:none;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.2), 0 25px 50px 0 rgba(0, 0, 0, 0.1);
  background: whitesmoke;
  color:#676767;

}
input:focus{
  outline: none;
}
input::placeholder{
  color:rgb(185, 185, 185);
}
button{
  height: 45px;
  width: 45px;
  cursor: pointer;
  background: none;
  border: none;
}
button:focus{
  outline: none;
}
.fas{
  font-size: large;
}
.synchronizing{
  animation: rotating 1.2s linear infinite;
}
@keyframes rotating {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
.fa-plus{
  color: green;
}
.fa-ellipsis-v, .fa-sync{
  color:#6b6b6b;
}
ul{  
  padding: 0;
  width: 80%;
  max-width: 400px;
}
li{
  display: block;
  border-bottom: 1px solid #ababab;
  padding: 10px 30px 0px;
  margin-bottom: -1px;
  cursor: pointer;
}
.modal-shrink{
  transform: scale(0.75) translateX(-50px);
  transition: all .3s
}
.parent{
  transition: all .3s
}
.modal{
  position: absolute;
  top: 0;
  right: 0;
  background: #00000024;
  width: 100%;
  height: 100%;
  text-align: right;
}
.modal-elemente{
  padding: 30px;
  background-color: #424242;
  width: fit-content;
  float: right;
  height: 100%;
  box-sizing: border-box;
}
.modal-elemente>div>span{
  color:#f9f9f9;
  cursor: pointer;
}
.fa-times{
  font-size: xx-large;
}
.slide-enter-active, .slide-leave-active {
  transition: all .3s ease-in-out;
}
.slide-enter, .slide-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
  width: 200%;
  transform: translateX(150px);
}

</style>
