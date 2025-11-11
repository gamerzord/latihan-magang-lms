<template>
  <v-container fluid class="pa-0 conference-room">
    <!-- Header Bar -->
    <div class="header-bar">
      <div class="header-content">
        <div class="header-left">
          <span class="conference-title">{{ conference?.title }}</span>
          <v-chip size="small" color="success" class="ml-2">
            <v-icon size="small" start>mdi-record-circle</v-icon>
            Active
          </v-chip>
        </div>
        <div class="header-right">
          <span class="time-display">{{ currentTime }}</span>
          <v-chip size="small" class="ml-4">
            <v-icon size="small" start>mdi-account-multiple</v-icon>
            {{ participants.length + 1 }}
          </v-chip>
        </div>
      </div>
    </div>

    <!-- Video Grid Container -->
    <div class="video-grid-container">
      <div 
        class="video-grid"
        :class="gridClass"
      >
        <!-- Local Video -->
        <div class="video-tile">
          <video
            ref="localVideoRef"
            autoplay
            playsinline
            muted
            class="video-element"
            :class="{ 'video-off': !isVideoOn }"
          ></video>
          <div v-if="!isVideoOn" class="video-off-placeholder">
            <v-avatar size="80" color="primary">
              <v-icon size="50" color="white">mdi-account</v-icon>
            </v-avatar>
          </div>
          <div class="video-name-tag">
            <v-icon size="16" class="mr-1">{{ isMicOn ? 'mdi-microphone' : 'mdi-microphone-off' }}</v-icon>
            {{ userName }} (You)
          </div>
        </div>

        <!-- Remote Videos -->
        <div 
          v-for="participant in participants"
          :key="participant.peerId"
          class="video-tile"
        >
          <video
            :ref="el => setRemoteVideoRef(el, participant.peerId)"
            autoplay
            playsinline
            class="video-element"
          ></video>
          <div class="video-name-tag">
            <v-icon size="16" class="mr-1">mdi-account</v-icon>
            {{ participant.name }}
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Control Bar -->
    <div class="control-bar">
      <div class="control-bar-content">
        <!-- Left: Meeting Info -->
        <div class="control-left">
          <span class="meeting-info">{{ conference?.course?.title }}</span>
        </div>

        <!-- Center: Main Controls -->
        <div class="control-center">
          <v-tooltip text="Microphone" location="top">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                :class="['control-btn', { 'control-btn-active': !isMicOn }]"
                size="large"
                icon
                @click="toggleMic"
              >
                <v-icon size="24">{{ isMicOn ? 'mdi-microphone' : 'mdi-microphone-off' }}</v-icon>
              </v-btn>
            </template>
          </v-tooltip>

          <v-tooltip text="Camera" location="top">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                :class="['control-btn', { 'control-btn-active': !isVideoOn }]"
                size="large"
                icon
                @click="toggleVideo"
              >
                <v-icon size="24">{{ isVideoOn ? 'mdi-video' : 'mdi-video-off' }}</v-icon>
              </v-btn>
            </template>
          </v-tooltip>

          <v-tooltip text="Leave Conference" location="top">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                class="leave-btn"
                size="large"
                icon
                @click="leaveConference"
              >
                <v-icon size="24">mdi-phone-hangup</v-icon>
              </v-btn>
            </template>
          </v-tooltip>
        </div>

        <!-- Right: Additional Info -->
        <div class="control-right">
          <span class="connection-status">
            <v-icon size="16" color="success" class="mr-1">mdi-circle</v-icon>
            Connected
          </span>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <v-overlay 
      v-model="loading" 
      class="align-center justify-center"
      persistent
    >
      <div class="text-center">
        <v-progress-circular
          indeterminate
          color="white"
          size="64"
        ></v-progress-circular>
        <p class="text-white mt-4 text-h6">Joining conference...</p>
      </div>
    </v-overlay>

    <!-- Snackbar -->
    <v-snackbar 
      v-model="snackbar.show" 
      :color="snackbar.color" 
      :timeout="3000"
      location="top"
    >
      {{ snackbar.text }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import Peer, { type MediaConnection, type DataConnection } from 'peerjs'
import type { Conference } from '~/types/models'

definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const config = useRuntimeConfig()
const { user } = useAuth()

const conference = ref<Conference | null>(null)
const loading = ref(true)
const localVideoRef = ref<HTMLVideoElement | null>(null)
const remoteVideoRefs = ref<Map<string, HTMLVideoElement>>(new Map())

const peer = ref<Peer | null>(null)
const localStream = ref<MediaStream | null>(null)
const calls = ref<Map<string, MediaConnection>>(new Map())
const dataConnections = ref<Map<string, DataConnection>>(new Map())

const participants = ref<Array<{ peerId: string; name: string }>>([])
const isMicOn = ref(true)
const isVideoOn = ref(true)
const currentTime = ref('')

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Get user name from auth
const userName = computed(() => {
  return user.value?.name || 'Teacher'
})

const gridClass = computed(() => {
  const totalParticipants = participants.value.length + 1
  if (totalParticipants === 1) return 'grid-1'
  if (totalParticipants === 2) return 'grid-2'
  if (totalParticipants <= 4) return 'grid-4'
  if (totalParticipants <= 6) return 'grid-6'
  return 'grid-9'
})

const { data: conferenceData } = useFetch<{ conference: Conference }>(
  `${config.public.apiBase}/conferences/${route.params.id}`,
  {
    key: `conference-${route.params.id}`,
    lazy: false,
  }
)

watch(conferenceData, (newData) => {
  if (newData) {
    conference.value = newData.conference
    if (!peer.value) {
      initializePeer()
    }
  }
}, { immediate: true })

const setRemoteVideoRef = (el: any, peerId: string) => {
  if (el) {
    remoteVideoRefs.value.set(peerId, el as HTMLVideoElement)
  }
}

const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true 
  })
}

// ADD THIS FUNCTION - Send user info when students connect
const sendUserInfoToStudent = (studentPeerId: string) => {
  const dataConnection = dataConnections.value.get(studentPeerId)
  if (dataConnection && dataConnection.open) {
    dataConnection.send({
      type: 'user-info',
      userName: userName.value,
      userId: user.value?.id
    })
  }
}

const callStudent = (studentPeerId: string) => {
  if (!peer.value || !localStream.value) {
    console.log('Peer or local stream not ready')
    return
  }
  
  console.log('Calling student:', studentPeerId)
  
  try {
    const call = peer.value.call(studentPeerId, localStream.value)
    
    call.on('stream', (remoteStream) => {
      console.log('Received stream from student:', studentPeerId)
      handleNewStream(studentPeerId, remoteStream)
    })
    
    call.on('close', () => {
      console.log('Call closed with student:', studentPeerId)
      removeParticipant(studentPeerId)
    })
    
    call.on('error', (error) => {
      console.error('Call error with student:', studentPeerId, error)
      showSnackbar('Failed to connect to student', 'error')
    })
    
    calls.value.set(studentPeerId, call)
  } catch (error) {
    console.error('Error calling student:', error)
  }
}

const initializePeer = async () => {
  try {
    localStream.value = await navigator.mediaDevices.getUserMedia({
      video: true,
      audio: true
    })

    if (localVideoRef.value) {
      localVideoRef.value.srcObject = localStream.value
    }

    const peerId = `teacher-${conference.value?.room_id}`
    
    peer.value = new Peer(peerId, {
      host: '0.peerjs.com',
      port: 443,
      secure: true,
      path: '/',
      config: {
        iceServers: [
          { urls: 'stun:stun.l.google.com:19302' },
          { urls: 'stun:global.stun.twilio.com:3478' }
        ]
      }
    })

    peer.value.on('open', (id) => {
      console.log('Teacher peer ID:', id)
      showSnackbar('Conference started - Waiting for students', 'success')
      loading.value = false
    })

    peer.value.on('call', (call) => {
      console.log('Incoming call from:', call.peer)
      
      call.answer(localStream.value!)
      
      call.on('stream', (remoteStream) => {
        handleNewStream(call.peer, remoteStream)
      })

      call.on('close', () => {
        removeParticipant(call.peer)
      })

      calls.value.set(call.peer, call)
    })

    peer.value.on('connection', (dataConnection) => {
      console.log('Data connection from:', dataConnection.peer)
      
      dataConnection.on('open', () => {
        console.log('Data connection opened with:', dataConnection.peer)
        showSnackbar('Student joined the conference', 'info')
        // Send user info to student when connection opens
        sendUserInfoToStudent(dataConnection.peer)
      })
      
      dataConnection.on('data', (data: any) => {
        console.log('Data received from student:', data)
        if (data.type === 'student-joined') {
          // Store student user info if provided
          if (data.userName) {
            const existingParticipant = participants.value.find(p => p.peerId === data.studentId)
            if (existingParticipant) {
              existingParticipant.name = data.userName
            }
          }
          callStudent(data.studentId)
        } else if (data.type === 'user-info') {
          // Update participant name with actual user name
          const existingParticipant = participants.value.find(p => p.peerId === dataConnection.peer)
          if (existingParticipant && data.userName) {
            existingParticipant.name = data.userName
          }
        }
      })
      
      dataConnection.on('close', () => {
        console.log('Data connection closed with:', dataConnection.peer)
      })
      
      dataConnection.on('error', (error) => {
        console.error('Data connection error:', error)
      })
      
      dataConnections.value.set(dataConnection.peer, dataConnection)
    })

    peer.value.on('error', (error) => {
      console.error('Peer error:', error)
      showSnackbar('Connection error', 'error')
    })

  } catch (error) {
    console.error('Error initializing peer:', error)
    showSnackbar('Failed to access camera/microphone', 'error')
    loading.value = false
  }
}

const handleNewStream = (peerId: string, stream: MediaStream) => {
  // Check if participant already exists, if not add with temporary name
  if (!participants.value.find(p => p.peerId === peerId)) {
    participants.value.push({
      peerId,
      name: `Student ${participants.value.length + 1}`
    })
    showSnackbar('New student connected', 'success')
  }

  setTimeout(() => {
    const videoEl = remoteVideoRefs.value.get(peerId)
    if (videoEl) {
      videoEl.srcObject = stream
    }
  }, 100)
}

const removeParticipant = (peerId: string) => {
  const participantName = participants.value.find(p => p.peerId === peerId)?.name || 'Participant'
  participants.value = participants.value.filter(p => p.peerId !== peerId)
  calls.value.delete(peerId)
  dataConnections.value.delete(peerId)
  remoteVideoRefs.value.delete(peerId)
  showSnackbar(`${participantName} left the conference`, 'info')
}

const toggleMic = () => {
  if (localStream.value) {
    const audioTrack = localStream.value.getAudioTracks()[0]
    if (audioTrack) {
      audioTrack.enabled = !audioTrack.enabled
      isMicOn.value = audioTrack.enabled
      showSnackbar(isMicOn.value ? 'Microphone on' : 'Microphone off', 'info')
    }
  }
}

const toggleVideo = () => {
  if (localStream.value) {
    const videoTrack = localStream.value.getVideoTracks()[0]
    if (videoTrack) {
      videoTrack.enabled = !videoTrack.enabled
      isVideoOn.value = videoTrack.enabled
      showSnackbar(isVideoOn.value ? 'Camera on' : 'Camera off', 'info')
    }
  }
}

const leaveConference = () => {
  if (confirm('Are you sure you want to end the conference?')) {
    cleanup()
    navigateTo('/teacher/conferences')
  }
}

const cleanup = () => {
  if (localStream.value) {
    localStream.value.getTracks().forEach(track => track.stop())
  }

  calls.value.forEach(call => call.close())
  calls.value.clear()

  dataConnections.value.forEach(conn => conn.close())
  dataConnections.value.clear()

  if (peer.value) {
    peer.value.destroy()
  }
}

const showSnackbar = (text: string, color: string) => {
  snackbar.value = { show: true, text, color }
}

onMounted(() => {
  updateTime()
  setInterval(updateTime, 1000)
})

onBeforeUnmount(() => {
  cleanup()
})
</script>

<style scoped>
.conference-room {
  height: 100vh;
  background: #202124;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Header Bar */
.header-bar {
  background: #3c4043;
  padding: 12px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  z-index: 10;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
}

.conference-title {
  color: white;
  font-size: 16px;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
}

.time-display {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
}

/* Video Grid Container */
.video-grid-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  overflow: auto;
}

.video-grid {
  display: grid;
  gap: 8px;
  width: 100%;
  height: 100%;
  max-width: 1600px;
  margin: 0 auto;
}

.grid-1 {
  grid-template-columns: 1fr;
}

.grid-2 {
  grid-template-columns: repeat(2, 1fr);
}

.grid-4 {
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
}

.grid-6 {
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: repeat(2, 1fr);
}

.grid-9 {
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: repeat(3, 1fr);
}

/* Video Tile */
.video-tile {
  position: relative;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 16 / 9;
  min-height: 200px;
}

.video-element {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-element.video-off {
  display: none;
}

.video-off-placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #3c4043;
}

.video-name-tag {
  position: absolute;
  bottom: 12px;
  left: 12px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 14px;
  display: flex;
  align-items: center;
  backdrop-filter: blur(4px);
}

/* Control Bar */
.control-bar {
  background: #3c4043;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 16px 24px;
  z-index: 10;
}

.control-bar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1600px;
  margin: 0 auto;
}

.control-left,
.control-right {
  flex: 1;
  display: flex;
  align-items: center;
}

.control-right {
  justify-content: flex-end;
}

.control-center {
  display: flex;
  gap: 12px;
  align-items: center;
}

.meeting-info {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
}

.connection-status {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  display: flex;
  align-items: center;
}

/* Control Buttons */
.control-btn {
  background: rgba(255, 255, 255, 0.1) !important;
  color: white !important;
  border-radius: 50% !important;
  width: 56px !important;
  height: 56px !important;
}

.control-btn:hover {
  background: rgba(255, 255, 255, 0.2) !important;
}

.control-btn-active {
  background: #ea4335 !important;
}

.control-btn-active:hover {
  background: #d33426 !important;
}

.leave-btn {
  background: #ea4335 !important;
  color: white !important;
  border-radius: 50% !important;
  width: 56px !important;
  height: 56px !important;
}

.leave-btn:hover {
  background: #d33426 !important;
}

/* Responsive */
@media (max-width: 960px) {
  .grid-4,
  .grid-6,
  .grid-9 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .control-left,
  .control-right {
    display: none;
  }
}

@media (max-width: 600px) {
  .grid-2,
  .grid-4,
  .grid-6,
  .grid-9 {
    grid-template-columns: 1fr;
  }
  
  .control-center {
    gap: 8px;
  }
  
  .control-btn,
  .leave-btn {
    width: 48px !important;
    height: 48px !important;
  }
}
</style>