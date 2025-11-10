<template>
  <v-container fluid class="pa-0 conference-room">
    <!-- Header Bar -->
    <div class="header-bar">
      <div class="header-content">
        <div class="header-left">
          <span class="conference-title">{{ conference?.title }}</span>
          <v-chip size="small" :color="conferenceStatusColor" class="ml-2">
            <v-icon size="small" start>{{ conferenceStatusIcon }}</v-icon>
            {{ conferenceStatusText }}
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

        <!-- Waiting for Teacher -->
        <div v-if="!hasTeacherConnected && conference?.status !== 'ended'" class="waiting-teacher">
          <div class="waiting-content">
            <v-progress-circular
              indeterminate
              color="primary"
              size="64"
            ></v-progress-circular>
            <p class="mt-4 text-h6">Waiting for teacher to join...</p>
            <p class="text-body-1 mt-2">Please wait while we connect you to the conference</p>
          </div>
        </div>

        <!-- Conference Ended Message -->
        <div v-if="conference?.status === 'ended'" class="conference-ended">
          <div class="ended-content">
            <v-icon size="80" color="grey-lighten-1" class="mb-4">mdi-phone-hangup</v-icon>
            <p class="text-h4 mb-2">Conference Ended</p>
            <p class="text-body-1 mb-4">The teacher has ended this conference.</p>
            <p class="text-caption text-medium-emphasis">Redirecting to dashboard...</p>
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
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                :class="['control-btn', { 'control-btn-active': !isMicOn }]"
                size="large"
                icon
                @click="toggleMic"
                :disabled="!hasTeacherConnected || conference?.status === 'ended'"
              >
                <v-icon size="24">{{ isMicOn ? 'mdi-microphone' : 'mdi-microphone-off' }}</v-icon>
              </v-btn>
            </template>
          </v-tooltip>

          <v-tooltip text="Camera" location="top">
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                :class="['control-btn', { 'control-btn-active': !isVideoOn }]"
                size="large"
                icon
                @click="toggleVideo"
                :disabled="!hasTeacherConnected || conference?.status === 'ended'"
              >
                <v-icon size="24">{{ isVideoOn ? 'mdi-video' : 'mdi-video-off' }}</v-icon>
              </v-btn>
            </template>
          </v-tooltip>

          <v-tooltip text="Leave Conference" location="top">
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                class="leave-btn"
                size="large"
                icon
                @click="leaveConference"
                :disabled="conference?.status === 'ended'"
              >
                <v-icon size="24">mdi-phone-hangup</v-icon>
              </v-btn>
            </template>
          </v-tooltip>
        </div>

        <!-- Right: Additional Info -->
        <div class="control-right">
          <span class="connection-status">
            <v-icon 
              size="16" 
              :color="connectionStatus.color" 
              class="mr-1"
            >mdi-circle</v-icon>
            {{ connectionStatus.text }}
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
        <p class="text-white mt-2">Setting up your camera and microphone</p>
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

const participants = ref<Array<{ peerId: string; name: string; isTeacher: boolean }>>([])
const isMicOn = ref(true)
const isVideoOn = ref(true)
const currentTime = ref('')
const hasTeacherConnected = ref(false)
const connectionAttempts = ref(0)
const maxConnectionAttempts = 5
const conferenceCheckInterval = ref<number | null>(null)

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Get user name from auth
const userName = computed(() => {
  return user.value?.name || 'Student'
})

// Conference status computed properties
const conferenceStatusText = computed(() => {
  if (conference.value?.status === 'ended') return 'Ended'
  if (conference.value?.status === 'active') return 'Active'
  return 'Connecting'
})

const conferenceStatusColor = computed(() => {
  if (conference.value?.status === 'ended') return 'error'
  if (conference.value?.status === 'active') return 'success'
  return 'warning'
})

const conferenceStatusIcon = computed(() => {
  if (conference.value?.status === 'ended') return 'mdi-phone-hangup'
  if (conference.value?.status === 'active') return 'mdi-record-circle'
  return 'mdi-clock-outline'
})

const connectionStatus = computed(() => {
  if (conference.value?.status === 'ended') {
    return { text: 'Conference Ended', color: 'error' }
  }
  if (!hasTeacherConnected.value) {
    return { text: 'Connecting...', color: 'warning' }
  }
  return { text: 'Connected', color: 'success' }
})

const gridClass = computed(() => {
  const totalParticipants = participants.value.length + 1
  if (totalParticipants === 1) return 'grid-1'
  if (totalParticipants === 2) return 'grid-2'
  if (totalParticipants <= 4) return 'grid-4'
  if (totalParticipants <= 6) return 'grid-6'
  return 'grid-9'
})

// Fetch conference data with periodic refreshing
const { data: conferenceData, refresh: refreshConference } = useFetch<{ conference: Conference }>(
  `${config.public.apiBase}/conferences/${route.params.id}`,
  {
    key: `conference-${route.params.id}`,
    lazy: false,
  }
)

// Watch for conference data changes
watch(conferenceData, (newData) => {
  if (newData) {
    conference.value = newData.conference
    console.log('Conference status:', conference.value?.status)
    
    // If conference ended, handle cleanup and redirect
    if (conference.value?.status === 'ended') {
      handleConferenceEnded()
    } else if (!peer.value) {
      initializePeer()
    }
  }
}, { immediate: true })

const startConferenceStatusCheck = () => {
  // Check conference status every 10 seconds
  conferenceCheckInterval.value = setInterval(async () => {
    try {
      await refreshConference()
    } catch (error) {
      console.error('Error checking conference status:', error)
    }
  }, 10000) as unknown as number
}

const handleConferenceEnded = () => {
  console.log('Conference ended, cleaning up...')
  
  // Show ended message
  showSnackbar('Conference has ended', 'info')
  
  // Clean up resources
  cleanup()
  
  // Redirect to dashboard after 3 seconds
  setTimeout(() => {
    navigateTo('/student')
  }, 3000)
}

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

const initializePeer = async () => {
  try {
    // Get user media first
    localStream.value = await navigator.mediaDevices.getUserMedia({
      video: true,
      audio: true
    })

    if (localVideoRef.value) {
      localVideoRef.value.srcObject = localStream.value
    }

    // Student peer ID - simpler format for easier connection
    const peerId = `student-${conference.value?.room_id}-${Math.random().toString(36).substr(2, 9)}`
    
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
      console.log('Student peer ID:', id)
      showSnackbar('Connected to conference server', 'success')
      loading.value = false
      
      // Start conference status checking
      startConferenceStatusCheck()
      
      // Start trying to connect to teacher
      connectToTeacher()
    })

    peer.value.on('call', (call) => {
      console.log('Incoming call from:', call.peer)
      
      // Don't answer if conference ended
      if (conference.value?.status === 'ended') {
        call.close()
        return
      }
      
      // Answer the call with local stream
      call.answer(localStream.value!)
      
      call.on('stream', (remoteStream) => {
        console.log('Received remote stream from:', call.peer)
        handleNewStream(call.peer, remoteStream)
      })

      call.on('close', () => {
        console.log('Call closed from:', call.peer)
        removeParticipant(call.peer)
      })

      call.on('error', (error) => {
        console.error('Call error:', error)
        showSnackbar('Connection error with teacher', 'error')
      })

      calls.value.set(call.peer, call)
    })

    // Handle data connections for messaging
    peer.value.on('connection', (dataConnection) => {
      console.log('Data connection from:', dataConnection.peer)
      dataConnection.on('data', (data: any) => {
        handleDataMessage(dataConnection.peer, data)
      })
      dataConnections.value.set(dataConnection.peer, dataConnection)
    })

    peer.value.on('error', (error) => {
      console.error('Peer error:', error)
      showSnackbar('Connection error: ' + error.message, 'error')
    })

    peer.value.on('disconnected', () => {
      console.log('Peer disconnected')
      showSnackbar('Disconnected from conference', 'warning')
    })

  } catch (error) {
    console.error('Error initializing peer:', error)
    showSnackbar('Failed to access camera/microphone', 'error')
    loading.value = false
  }
}

const connectToTeacher = () => {
  // Don't try to connect if conference ended
  if (conference.value?.status === 'ended') {
    return
  }

  if (connectionAttempts.value >= maxConnectionAttempts) {
    showSnackbar('Failed to connect to teacher after multiple attempts', 'error')
    return
  }

  const teacherPeerId = `teacher-${conference.value?.room_id}`
  console.log('Attempting to connect to teacher:', teacherPeerId)
  
  connectionAttempts.value++

  // Try to call teacher after a short delay
  setTimeout(() => {
    if (peer.value && !peer.value.disconnected && conference.value?.status !== 'ended') {
      console.log(`Connection attempt ${connectionAttempts.value} to teacher`)
      
      // Also try to establish data connection
      try {
        const dataConnection = peer.value.connect(teacherPeerId, {
          reliable: true
        })
        
        dataConnection.on('open', () => {
          console.log('Data connection established with teacher')
          // Send user info when connecting
          dataConnection.send({ 
            type: 'student-joined',
            studentId: peer.value?.id,
            userName: userName.value,
            userId: user.value?.id
          })
        })
        
        dataConnection.on('data', (data: any) => {
          console.log('Data received from teacher:', data)
          if (data.type === 'user-info') {
            // Update teacher's name with actual user name
            const teacherParticipant = participants.value.find(p => p.isTeacher)
            if (teacherParticipant && data.userName) {
              teacherParticipant.name = data.userName
            }
          }
        })
        
        dataConnection.on('error', (error) => {
          console.error('Data connection error:', error)
        })
        
        dataConnection.on('close', () => {
          console.log('Data connection closed with teacher')
        })
        
        dataConnections.value.set(teacherPeerId, dataConnection)
      } catch (error) {
        console.error('Error creating data connection:', error)
      }
    }
    
    // Retry if not connected and conference still active
    if (!hasTeacherConnected.value && conference.value?.status !== 'ended') {
      setTimeout(connectToTeacher, 2000)
    }
  }, 1000)
}

const handleNewStream = (peerId: string, stream: MediaStream) => {
  console.log('Handling new stream from:', peerId)
  
  const isTeacher = peerId.startsWith('teacher-')
  const name = isTeacher ? 'Teacher' : `Student ${participants.value.length}`
  
  if (!participants.value.find(p => p.peerId === peerId)) {
    participants.value.push({ 
      peerId, 
      name, 
      isTeacher 
    })
    
    if (isTeacher) {
      hasTeacherConnected.value = true
      showSnackbar('Connected to teacher!', 'success')
    }
  }

  // Set the stream to the video element
  nextTick(() => {
    const videoEl = remoteVideoRefs.value.get(peerId)
    if (videoEl) {
      videoEl.srcObject = stream
      console.log('Stream set to video element for:', peerId)
    } else {
      console.warn('Video element not found for:', peerId)
    }
  })
}

const handleDataMessage = (peerId: string, data: any) => {
  console.log('Data message from:', peerId, data)
  
  // Handle conference end messages from teacher
  if (data.type === 'conference-ended') {
    handleConferenceEnded()
  } else if (data.type === 'user-info') {
    // Update participant name with actual user name
    const participant = participants.value.find(p => p.peerId === peerId)
    if (participant && data.userName) {
      participant.name = data.userName
    }
  }
}

const removeParticipant = (peerId: string) => {
  const participant = participants.value.find(p => p.peerId === peerId)
  if (participant?.isTeacher) {
    hasTeacherConnected.value = false
    showSnackbar('Teacher has left the conference', 'warning')
  }
  
  participants.value = participants.value.filter(p => p.peerId !== peerId)
  calls.value.delete(peerId)
  dataConnections.value.delete(peerId)
  remoteVideoRefs.value.delete(peerId)
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
  if (confirm('Are you sure you want to leave the conference?')) {
    cleanup()
    navigateTo('/student')
  }
}

const cleanup = () => {
  // Clear conference check interval
  if (conferenceCheckInterval.value) {
    clearInterval(conferenceCheckInterval.value)
    conferenceCheckInterval.value = null
  }

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

.waiting-teacher {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  color: white;
  z-index: 5;
}

.waiting-content {
  background: rgba(0, 0, 0, 0.8);
  padding: 40px;
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.conference-ended {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  color: white;
  z-index: 10;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ended-content {
  background: rgba(0, 0, 0, 0.9);
  padding: 60px 40px;
  border-radius: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Add disabled state for controls */
.control-btn:disabled,
.leave-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.control-btn:disabled:hover,
.leave-btn:disabled:hover {
  background: rgba(255, 255, 255, 0.1) !important;
}
</style>