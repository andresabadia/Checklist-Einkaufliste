<template>
  <div>
    <div class="parent" :class="{'modal-shrink':showMenu}">
      <div class="top">
        <div class="title">Einkaufliste</div>
        <div class="input">
          <input id="input" type="text" placeholder="z.B. Milch"  v-model="newItemInput" @keyup.enter="addItem" @input="newItemInputCheck">
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
    </div>
    <transition name="slide">
      <div class="modal" @click="modal" v-if="showMenu">
        <div class="modal-elemente">
          <div @click="showMenu = !showMenu"><span><i class="fas fa-times"></i></span></div>
          <div><span @click="emptyList">Liste Leeren <i class="far fa-file"></i></span></div>
          <div><span @click="removeChecked">Gekaufte Löschen <i class="fas fa-trash-alt"></i></span></div>  
          <div><span @click="checkedToBottom">Gekauft nach unten <i class="fas fa-file-download"></i></span></div>         
          <div><span @click="shareList">Einkaufliste Teilen <i class="fas fa-file-export"></i></span></div>
          <hr>
          <div><span @click="listNew">Neue Liste <i class="fas fa-file-medical"></i></span></div>
          <div><span @click="login">Login <i class="fas fa-user"></i></span></div>
          <div><span @click="help">Hilfe <i class="fas fa-info-circle"></i></span></div>
        </div>         
      </div>
    </transition>
    <div class="share-link-parent" v-show="sharing"><input id="share-link" :class="{'share-link-hidden':shareLinkHide}" type="text" v-model="shareLink"></div>
    <transition name="slide-up">
      <div class="toast" v-if="showToast"><span>{{toastMessage}}</span></div>  
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
      changesCount: 0,  //counter for locally made changes    
      newItemInput:'', // Input field where new items are added
      showMenu:false, // bool if menu is visible or not
      showSync: true, // bool if sync button is show or add button
      statusSyncData: false, //bool to control the class of the sync button to animate while synchronizing
      syncStatus:'', // informative string wich shows the user the actual state od the sync
      clicked: 0, // helper integer to count clicks for double tap functionability
      itemIndexTmp: '', // helper string to control if the bouble click/tab was made to the same index
      shareLink:'', // string with the link to share
      sharing:false, // bool if input from where the shareLink will be copied is visible or not
      shareLinkHide:true, // bool to control the invisible class of the input from where the shareLink will be copied
      showToast:false, // bool if toast is visible or not
      toastMessage:'Toast Message' // message which will be display on the toast
    }
  },
  created(){
    this.setDefaultVariables()
    let items = localStorage.getItem('items')
    let listID = localStorage.getItem('listID')
    let lastUpdate = localStorage.getItem('lastUpdate')
    let changes = localStorage.getItem('changes')
    
    if (location.search!=''){
      let query = location.search
      this.list.ID = query.slice(1, query.length)
      if (listID != this.list.ID){
        this.removeDataLocal()//empty local storage
        this.list.timestamp=0
        this.changesCount=0
        this.syncData()
      }
    }
    if(items!=null){
      this.list.items=JSON.parse(items)
    }
    if(listID!=null){
      this.list.ID=listID
      if(location.search==''){
        history.pushState({}, '', '?' + this.list.ID)
      }
      this.syncData()
      }
    if(lastUpdate!=null){
      this.list.timestamp=lastUpdate
      }
    if(changes!=null){
      this.changesCount=changes
      }
    
    document.addEventListener("visibilitychange", () => {
      // console.log(document.hidden)
      }, false)
  },
  methods:{
    setDefaultVariables(){
      this.changesCount = 0   
      this.newItemInput=''
      this.showMenu=false
      this.shareLink=''
      this.syncStatus=''
      this.list.ID=false
      this.list.timestamp=false
      this.list.items=[]
    },
    createList(data){
      axios.post('./php/updateList.php', data)
        .then(res => {
          console.log(res, data, this.list.ID, this.list.timestamp)
          localStorage.setItem('listID', this.list.ID)
          history.pushState({}, '', '?' + this.list.ID)
          localStorage.setItem('lastUpdate', this.list.timestamp)
          this.syncStatus = 'List created and updated'
          this.statusSyncData = false
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
          console.log('server: ' + res.data.timestamp +  'local: ' + this.list.timestamp)

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

          //third party changes or shared list
          if((lastUpdate < serverTimestamp && this.changesCount == 0 || !this.list.timestamp)){
            console.log('third party changes or shared list')
            this.list.items = res.data.items            
            localStorage.setItem('items', JSON.stringify(this.list.items))
            this.list.timestamp = res.data.timestamp
            localStorage.setItem('lastUpdate', this.list.timestamp)
            this.list.ID = res.data.ID
            localStorage.setItem('listID', this.list.ID)
            this.statusSyncData = false
            return
          }

          //local changes and third party changes
          if(lastUpdate < serverTimestamp && this.changesCount != 0){
            console.log('local and third party changes')
            this.compareChanges(res.data.items, this.list.items, [], lastUpdate, serverTimestamp)
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
              // console.log("same item and status ", serverList[i], localList[j])
              newList.push(serverList[i]) // No Changes
              serverList.splice(i, 1)
              localList.splice(j, 1)
              if (serverList.length > 0){
                this.compareChanges(serverList, localList, newList, lastUpdate, serverTimestamp)
                return
              } else { 
                // serverlist empty
                if (localList.length > 0){
                  this.compareLocalChanges(localList, newList, lastUpdate)
                  return
                } else {
                  // console.log("Ende1", newList)
                  this.list.items = newList
                  this.list.timestamp = new Date().toJSON()
                  this.updateList(this.list) // pass newList
                  return
                }    
              }
            } else { // status are not the same it means changes where made checked has priority!
              // console.log("same item different status", serverList[i], localList[j])
              serverList[i].status = 'checked' 
              newList.push(serverList[i])
              serverList.splice(i, 1)
              localList.splice(j, 1)
              if (serverList.length > 0){
                this.compareChanges(serverList, localList, newList, lastUpdate, serverTimestamp)
                return
              } else {
                // serverlist empty
                if (localList.length > 0){
                  this.compareLocalChanges(localList, newList, lastUpdate)
                  return
                } else {
                  // console.log("Ende2", newList)
                  this.list.items = newList
                  this.list.timestamp = new Date().toJSON()
                  this.updateList(this.list) // pass newList
                  return
                }                  
              }
            }
          }
        }
        // item dont exist in local
        if (new Date(serverList[i].timestamp).getTime() > lastUpdate) {
          // console.log("item has been added by 3rd party", serverList[i])
          newList.unshift(serverList[i])
          serverList.splice(i, 1)
          if (serverList.length > 0){
              this.compareChanges(serverList, localList, newList, lastUpdate, serverTimestamp)
              return
            } else {
              // serverlist empty
              if (localList.length > 0){
                this.compareLocalChanges(localList, newList, lastUpdate)
                return
              } else {
                // console.log("Ende3", newList)
                this.list.items = newList
                this.list.timestamp = new Date().toJSON()
                this.updateList(this.list) // pass newList
                return
              }                  
            }
          } else {
            // console.log("item has been removed locally", serverList[i], new Date(lastUpdate).toJSON())
            serverList.splice(i, 1)            
            if (serverList.length > 0){
              this.compareChanges(serverList, localList, newList, lastUpdate, serverTimestamp)
              return
            } else {
              // serverlist empty
              if (localList.length > 0){
                this.compareLocalChanges(localList, newList, lastUpdate)
                return
              } else {
                // console.log("Ende4", newList)
                this.list.items = newList
                this.list.timestamp = new Date().toJSON()
                this.updateList(this.list) // pass newList
                return
              }  
            }
          }  
      }
    },
    compareLocalChanges(localList, newList, lastUpdate){
      for (let i=0; i < localList.length; i++){
        if(new Date(localList[i].timestamp).getTime() > lastUpdate){
          // console.log("item has been added locally", localList[i])
          newList.unshift(localList[i])
          localList.splice(i,1)
          if (localList.length > 0) {
            this.compareLocalChanges(localList, newList, lastUpdate)
            return
          } else {
            // console.log("Ende5", newList)
            this.list.items = newList
            this.list.timestamp = new Date().toJSON()
            this.updateList(this.list) // pass newList
            return
          }
        } else {
          // console.log("item has been removed by 3rd Party", localList[i])
          localList.splice(i,1)
          if (localList.length > 0) {
            this.compareLocalChanges(localList, newList, lastUpdate)
            return
          } else {
            // console.log("Ende6", newList)
            this.list.items = newList
            this.list.timestamp = new Date().toJSON()
            this.updateList(this.list) // pass newList
            return
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
    },
    syncDataLocal(){
      this.changesCount++
      this.syncStatus = 'last changes: ' + this.changesCount
      localStorage.setItem('changes', this.changesCount)
      localStorage.setItem('items', JSON.stringify(this.list.items))
    },
    removeDataLocal(){
      localStorage.removeItem('items');
      localStorage.removeItem('changes');
      localStorage.removeItem('lastUpdated')
      localStorage.removeItem('listID')
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
      this.syncDataLocal()

      let vm=this
      this.clicked++
      if(this.clicked==1){
        this.itemIndexTmp = index
        setTimeout(()=>{
          this.clicked=0
          this.syncData()
        },300)
      }else{
        this.clicked=0
        if(index == this.itemIndexTmp){
          this.itemIndexTmp = ''
          this.changesCount--
          this.changesCount--
          this.removeItem(index)
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
      this.syncData()
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
      this.syncData()
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
      this.syncData()
    },
    shareList(){      
      if (this.list.ID != false){
        let loc = document.location
        this.shareLink = loc.origin + loc.pathname + loc.search
        this.sharing=true
        setTimeout(() => {
          let link = document.getElementById('share-link')
          link.focus()
          link.select()          
          let copyLink = document.execCommand('copy')   
          link.blur() 
          this.sharing = !copyLink
          this.shareLinkHide = copyLink 
          this.toast('Link würde kopiert')
        }, 100)
      } else {
        this.toast('Bitte zuerst Synchronizieren')
      } 
      this.showMenu=false    
    },
    listNew(){
      this.emptyList()
      this.setDefaultVariables()
      this.removeDataLocal()
      history.pushState({},'','/')
    },
    login(){
      this.toast('nicht verfügbar')
    },
    help(){
      this.toast('nicht verfügbar')
    },
    toast(message){
      this.showToast = true
      this.toastMessage = message
      setTimeout(()=>{
        this.showToast = false
      }, 2000)
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
  position: fixed;
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
  overflow-y: scroll;
}
.modal-elemente>div{
  margin: 10px 0
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
.share-link-parent{
  position:absolute;
  bottom:20px;
  left:0;
  width: 100%;
}
#share-link{
  width: 90%;
  max-width: unset;
  margin: 0 5%;
  padding: unset;
}
.share-link-hidden{
  color: transparent;
  background: transparent;
  box-shadow: unset;
}
.share-link-hidden::selection{
  color:transparent;
  background: transparent;
}
.toast{
  position: fixed;
  bottom: 0;
  display: flex;
  justify-content: center;
  width: 100%;
  padding: 10px 0;
  color: #f9f9f9;
}
.toast>span{  
  background: #424242e6;
  padding: 20px;
  box-shadow: 1px 1px 2px black, 0 0 25px #cccccc, 0 0 5px #9c9c9c;
}
.slide-up-enter-active, .slide-up-leave-active {
  transition: all .3s ease-in-out;
}
.slide-up-enter, .slide-up-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
  transform: translateY(150px);
}
</style>
