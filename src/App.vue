<template>
  <div>
    <div class="parent" :class="{'modal-shrink':showMenu}">
      <div class="top">
        <div class="title">Einkaufliste</div>
        <div class="input">
          <input type="text" placeholder="z.B. Milch"  v-model="newItemInput" @keyup.enter="addItem" @input="newItemInputCheck">
          <button @click="syncData" v-if="showSync"><i class="fas fa-sync" :class="{'synchronizing':statusSyncData}"></i></button> 
          <button @click="addItem" v-else><i class="fas fa-plus"></i></button>
          <button @click="showMenu = !showMenu"><i class="fas fa-ellipsis-v"></i></button>
          <div class="sync-status"> {{syncStatus}} </div>    
        </div>
      </div>
      <div class="liste">
        <transition-group name="main-list" tag="ul">>
          <li v-for="(item, index) in list.items" @click="checkItem(index)" :key="item.ID" :class="{'checked':item.status=='checked'}"> {{ item.item }} - ( {{item.quantity}} ) </li>
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
          <div><span @click="emptyList">Liste Leeren <i class="far fa-file"></i></span></div>
          <div><span @click="removeChecked">Gekaufte Löschen <i class="fas fa-trash-alt"></i></span></div>  
          <div><span @click="checkedToBottom">Gekauft nach unten <i class="fas fa-file-download"></i></span></div>         
          <div><span>Einkaufliste Teilen <i class="fas fa-file-export"></i></span></div>
          <div><span>Neue Liste <i class="fas fa-file-medical"></i></span></div>
          <div><span>Login <i class="fas fa-user"></i></span></div>
          <div><span>Hilfe <i class="fas fa-info-circle"></i></span></div>
        </div>      
      </div>
    </transition>
  </div>
      
</template>

<script>
import axios from 'axios'
export default {
  data(){
    return{
      list:{
        ID: false,
        timestamp: false,
        items:[
          {
            ID: '1',
            item: 'Milch',
            quantity: 1,
            timestamp: '1',
            userID:'',
            status: 'unchecked'
          },
          {
            ID: '2',
            item: 'Brot',
            quantity: 2,
            timestamp: '2',
            userID:'',
            status: 'checked'
          },
          {
            ID: '3',
            item: 'Zucker',
            quantity: 1,
            timestamp: '3',
            userID:'',
            status: 'unchecked'
          }
        ]
      },
      
      changesCount: 0,      
      newItemInput:'',
      showMenu:false,
      showSync: true,
      statusSyncData: false,
      syncStatus:'',
      clicked: 0,
      itemIndexTmp: ''
    }
  },
  created(){
    document.addEventListener("visibilitychange", () => {
      // console.log(document.hidden)
      }, false)
    if(localStorage.getItem('items')!=null){
      this.list.items=JSON.parse(localStorage.getItem('items'))
    }
    if(localStorage.getItem('listID')!=null){
        this.list.ID=localStorage.getItem('listID')
      }
    if(localStorage.getItem('lastUpdate')!=null){
        this.list.timestamp=localStorage.getItem('lastUpdate')
      }
    if(localStorage.getItem('changes')!=null){
        this.changesCount=localStorage.getItem('changes')
      }
  },
  methods:{
    createList(data){
      axios.post('./php/updateList.php', data)
        .then(res => {
          console.log(res, data, this.list.ID, this.list.timestamp)
          localStorage.setItem('listID', this.list.ID)
          history.pushState({}, '', '?' + this.list.ID)
          localStorage.setItem('lastUpdate', this.list.timestamp)
          this.syncStatus = 'List created and updated'
          vm.statusSyncData = false
        })
        .catch(error => {
          console.log(error)
          this.list.ID = false
          this.list.timestamp = false
          this.syncStatus = 'Something went wrong'
          this.statusSyncData = false
        })
    },
    updateList(data){
      axios.post('./php/updateList.php', data)
        .then(res => {
          console.log(res)
          localStorage.setItem('lastUpdate', this.list.timestamp)
          this.changesCount = 0
          localStorage.setItem('changes', this.changesCount)
          this.syncStatus = 'Update successful'
          this.statusSyncData = false
        })
        .catch(error => {
          console.log(error)
          this.list.timestamp = localStorage.getItem('lastUpdate')
          this.syncStatus = 'Update failed'
          this.statusSyncData = false
        })
    },
    getList(listID){
      axios.get('./lists/'+listID+'.json')
        .then(res => {
          console.log('server', res.data.timestamp, 'local', this.list.timestamp)

          let serverTimestamp = new Date(res.data.timestamp).getTime()
          let lastUpdate = new Date(this.list.timestamp).getTime()

          //check for updates
          if(lastUpdate == serverTimestamp && this.changesCount == 0){
            console.log('check for updates')
            this.statusSyncData = false
            return
          }

          //local changes
          if(lastUpdate == serverTimestamp && this.changesCount != 0){
            console.log('local changes')
            this.list.timestamp = new Date().toJSON()
            this.updateList(this.list)
            return
          }

          //third party changes
          if(lastUpdate < serverTimestamp && this.changesCount == 0){
            console.log('third party changes')
            this.list.items = res.data.items            
            localStorage.setItem('items', JSON.stringify(this.list.items))
            this.list.timestamp = res.data.timestamp
            localStorage.setItem('lastUpdate', vm.list.timestamp)
            this.statusSyncData = false
            return
          }

          //local changes and third party changes
          if(lastUpdate < serverTimestamp && this.changesCount != 0){
            console.log('local and third party changes')
            this.compareChanges(res.data.items, this.list.items, [], lastUpdate,serverTimestamp)
            return
          }
          this.statusSyncData = false
        })
        .catch(error => {
          console.log(error)
          this.statusSyncData = false
        })
    },
    compareChanges(serverList, localList, newList, lastUpdate, serverTimestamp){
      for (let i = 0; i < serverList.length; i++){
        for (let j = 0; j < localList.length; j++){
          if (serverList[i].ID == localList[j].ID) { // check if server item exist in local
            if (serverList[i].status == localList[j].status){ // check if status are the same it means no changes where made
              newList.push(serverList[i]) // No Changes
              serverList.splice(i, 1)
              localList.splice(j, 1)
              if (serverList.length > 0){
                this.compareChanges(serverList, localList, newList)
                return
              } else { 
                // serverlist empty
                if (localList.length > 0){
                  this.compareLocalChanges(localList, newList, serverTimestamp)
                } else {
                  this.list.items = newList
                  this.list.timestamp = new Date().toJSON()
                  this.updateList(this.list) // pass newList
                }               
                return
              }
            } else { // status are not the same it means changes where made checked has priority!
              serverList[i].status = 'checked' 
              newList.push(serverList[i])
              serverList.splice(i, 1)
              localList.splice(j, 1)
              if (serverList.length > 0){
                this.compareChanges(serverList, localList, newList)
                return
              } else {
                // serverlist empty
                if (localList.length > 0){
                  this.compareLocalChanges(localList, newList, serverTimestamp)
                } else {
                  this.list.items = newList
                  this.list.timestamp = new Date().toJSON()
                  this.updateList(this.list) // pass newList
                }  
                return
              }
            }
          }
          // item dont exist in local
          if (new Date(serverList[i].timestamp).getTime() > lastUpdate) {
            newList.unshift(serverList[i])
            serverList.splice(i, 1)
            if (serverList.length > 0){
                this.compareChanges(serverList, localList, newList)
                return
              } else {
                // serverlist empty
                if (localList.length > 0){
                  this.compareLocalChanges(localList, newList, serverTimestamp)
                } else {
                  this.list.items = newList
                  this.list.timestamp = new Date().toJSON()
                  this.updateList(this.list) // pass newList
                }  
                return
              }
          } else {
            serverList.splice(i, 1)
            if (serverList.length > 0){
                this.compareChanges(serverList, localList, newList)
                return
              } else {
                // serverlist empty
                if (localList.length > 0){
                  this.compareLocalChanges(localList, newList, serverTimestamp)
                } else {
                  this.list.items = newList
                  this.list.timestamp = new Date().toJSON()
                  this.updateList(this.list) // pass newList
                }  
                return
              }
          }          
        }
      }
    },
    compareLocalChanges(localList, newList, serverTimestamp){
      for (let i=0; i < localList.length; i++){
        if(new Date(localList[i].timestamp).getTime().timestamp > serverTimestamp){
          newList.unshift(localList[i])
          localList.splice(i,1)
          if (localList.length > 0) {
            compareLocalChanges(localList, newList, serverTimestamp)
            return
          } else {
            this.list.items = newList
            this.list.timestamp = new Date().toJSON()
            this.updateList(this.list) // pass newList
          }
        }
      }
    },
    syncData(){
      this.statusSyncData = true
      if(!this.list.ID) {
        this.list.ID = this.makeUniqueID()
        this.list.timestamp = new Date().toJSON()
        this.createList(this.list)
        return
      } else {
        this.getList(this.list.ID)
      }
      
      
      // history.pushState({}, '', '?'+this.list.ID)
      // let vm = this
      // this.statusSyncData = true
      // setTimeout(() => {
      //   this.statusSyncData = false
      // },1200)
    },
    syncDataLocal(){
      this.changesCount++
      this.syncStatus = 'last changes: ' + this.changesCount
      localStorage.setItem('changes', this.changesCount)
      localStorage.setItem('items', JSON.stringify(this.list.items))
    },
    removeDataLocal(){
      localStorage.removeItem('items');
    },
    removeItem(index){      
      if(this.list.items[index].status == 'checked'){
        this.list.items.splice(index, 1)
        this.syncDataLocal()
        return
      }
    },
    checkItem(index){
      if (this.list.items[index].status == 'unchecked'){
        this.list.items[index].status = 'checked' 
      }  else {
        this.list.items[index].status = 'unchecked'       
      }
      
      // this.items[index].timestamp = new Date().toJSON() 
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
      this.showSync = true
      if(this.newItemInput == ''){ return }
      let newItem={
        ID:'',
        item:'',
        quantity: 1,
        timestamp:'',
        userID:'',
        status: 'unchecked'
      }
      newItem.item = this.newItemInput
      newItem.timestamp = new Date().toJSON()
      newItem.ID = this.makeUniqueID()
      this.list.items.unshift(newItem)
      this.newItemInput = ''  
      this.syncDataLocal()
    },
    emptyList(){
      this.list.items=[]
      this.syncDataLocal()
      this.showMenu=false  
    },
    removeChecked(){
      this.checkedToBottom()
      let firstCheckedIndex = this.list.items.findIndex(firstChecked)
      if (firstCheckedIndex != -1){
        this.list.items.splice(firstCheckedIndex, this.list.items.length)
      }
      function firstChecked(object){
        return object.status == 'checked'
      }
      this.syncDataLocal()
      this.showMenu=false
    },
    checkedToBottom(){
      this.list.items.sort(compareStatus)
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
      this.list.items = _.shuffle(this.list.items)
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
    },
     newItemInputCheck(e){
      let val = e.target.value
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
.input {position: relative;}
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
.sync-status{
  position: absolute;
  bottom: -45px;
  width: 50%;
  height: 40px;
  line-height: 1.2;
  max-width: 400px;
  text-align: right;
  font-size: smaller;
  overflow: hidden;
  color:#a5a5a5;
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
