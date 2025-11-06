<template>
  <v-container fluid class="pa-0 fill-height">
    <v-row no-gutters class="fill-height">
      <v-col cols="12">
        <v-card class="fill-height d-flex flex-column" elevation="0">
          <!-- Header -->
          <v-card-title class="bg-primary text-white d-flex justify-space-between">
            <div>
              <v-icon color="white" class="mr-2">mdi-video</v-icon>
              {{ conference?.title }}
            </div>
            <div>
              <v-chip color="success" variant="elevated" class="mr-2">
                <v-icon left small>mdi-account-multiple</v-icon>
                {{ participants.length }} participants
              </v-chip>
              <v-btn
                color="error"
                variant="elevated"
                @click="leaveConference"
              >
                <v-icon left>mdi-phone-hangup</v-icon>
                Leave
              </v-btn>
            </div>
          </v-card-title>

          <!-- Video Grid -->
          <v-card-text class="flex-grow-1 pa-4" style="overflow-y: auto;">
            <v-row>
              <!-- Local Video -->
              <v-col cols="12" md="6" lg="4">
                <v-card elevation="3">
                  <div class="video-container">
                    <video
                      ref="localVideoRef"
                      autoplay
                      playsinline
                      muted
                      class="video-element"
                    ></video>
                    <div class="video-overlay">
                      <v-chip color="primary" small>You (Host)</v-chip>
                    </div>
                  </div>
                </v-card>
              </v-col>

              <!-- Remote Videos -->
              <v-col
                v-for="participant in participants"
                :key="participant.peerId"
                cols="12"
                md="6"
                lg="4"
              >
                <v-card elevation="3">
                  <div class="video-container">
                    <video
                      :ref="el => setRemoteVideoRef(el, participant.peerId)"
                      autoplay
                      playsinline
                      class="video-element"
                    ></video>
                    <div class="video-overlay">
                      <v-chip color="secondary" small>
                        {{ participant.name }}
                      </v-chip>
                    </div>
                  </div>
                </v-card>
              </v-col>
            </v-row>

            <!-- Loading State -->
            <v-row v-if="loading" justify="center" align="center" class="mt-8">
              <v-col cols="auto">
                <v-progress-circular
                  indeterminate
                  color="primary"
                  size="64"
                ></v-progress-circular>
                <p class="text-center mt-4">Setting up conference...</p>
              </v-col>
            </v-row>
          </v-card-text>

          <!-- Controls -->
          <v-card-actions class="justify-center pa-4 bg-grey-lighten-4">
            <v-btn
              :color="isMicOn ? 'primary' : 'error'"
              size="large"
              icon
              @click="toggleMic"
              class="mx-2"
            >
              <v-icon>{{ isMicOn ? 'mdi-microphone' : 'mdi-microphone-off' }}</v-icon>
            </v-btn>

            <v-btn
              :color="isVideoOn ? 'primary' : 'error'"
              size="large"
              icon
              @click="toggleVideo"
              class="mx-2"
            >
              <v-icon>{{ isVideoOn ? 'mdi-video' : 'mdi-video-off' }}</v-icon>
            </v-btn>

            <v-btn
              color="blue"
              size="large"
              icon
              @click="toggleScreenShare"
              class="mx-2"
            >
              <v-icon>{{ isScreenSharing ? 'mdi-monitor-off' : 'mdi-monitor-share' }}</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </v-container>
</template>

<script setup lang="ts">
import Peer, { type MediaConnection } from 'peerjs'
import type { Conference } from '~/types/models'

const route = useRoute()
const config = useRuntimeConfig()

const conference = ref<Conference | null>(null)
const loading = ref(true)
const localVideoRef = ref<HTMLVideoElement | null>(null)
const remoteVideoRefs = ref<Map<string, HTMLVideoElement>>(new Map())

const peer = ref<Peer | null>(null)
const localStream = ref<MediaStream | null>(null)
const calls = ref<Map<string, MediaConnection>>(new Map())

const participants = ref<Array<{ peerId: string; name: string }>>([])
const isMicOn = ref(true)
const isVideoOn = ref(true)
const isScreenSharing = ref(false)
const screenStream = ref<MediaStream | null>(null)

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
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

const initializePeer = async () => {
  try {
    localStream.value = await navigator.mediaDevices.getUserMedia({
      video: true,
      audio: true
    })

    if (localVideoRef.value) {
      localVideoRef.value.srcObject = localStream.value
    }

    const peerId = `teacher-${conference.value?.room_id}-${Date.now()}`
    
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
      console.log('My peer ID:', id)
      showSnackbar('Connected to conference', 'success')
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
  if (!participants.value.find(p => p.peerId === peerId)) {
    participants.value.push({
      peerId,
      name: `Student ${participants.value.length + 1}`
    })
  }

  setTimeout(() => {
    const videoEl = remoteVideoRefs.value.get(peerId)
    if (videoEl) {
      videoEl.srcObject = stream
    }
  }, 100)
}

const removeParticipant = (peerId: string) => {
  participants.value = participants.value.filter(p => p.peerId !== peerId)
  calls.value.delete(peerId)
  remoteVideoRefs.value.delete(peerId)
  showSnackbar('A participant left', 'info')
}

const toggleMic = () => {
  if (localStream.value) {
    const audioTrack = localStream.value.getAudioTracks()[0]
    if (audioTrack) {
      audioTrack.enabled = !audioTrack.enabled
      isMicOn.value = audioTrack.enabled
    }
  }
}

const toggleVideo = () => {
  if (localStream.value) {
    const videoTrack = localStream.value.getVideoTracks()[0]
    if (videoTrack) {
      videoTrack.enabled = !videoTrack.enabled
      isVideoOn.value = videoTrack.enabled
    }
  }
}

const toggleScreenShare = async () => {
  try {
    if (!isScreenSharing.value) {
      screenStream.value = await navigator.mediaDevices.getDisplayMedia({
        video: true
      })

      const videoTrack = screenStream.value.getVideoTracks()[0]
      
      if (videoTrack) {
        calls.value.forEach((call) => {
          const sender = (call as any).peerConnection
            ?.getSenders()
            .find((s: RTCRtpSender) => s.track?.kind === 'video')
          
          if (sender) {
            sender.replaceTrack(videoTrack)
          }
        })

        if (localVideoRef.value) {
          localVideoRef.value.srcObject = screenStream.value
        }

        videoTrack.onended = () => {
          stopScreenShare()
        }

        isScreenSharing.value = true
        showSnackbar('Screen sharing started', 'success')
      }
    } else {
      stopScreenShare()
    }
  } catch (error) {
    console.error('Screen share error:', error)
    showSnackbar('Failed to share screen', 'error')
  }
}

const stopScreenShare = () => {
  if (screenStream.value) {
    screenStream.value.getTracks().forEach(track => track.stop())
    screenStream.value = null
  }

  if (localStream.value) {
    const videoTrack = localStream.value.getVideoTracks()[0]
    
    calls.value.forEach((call) => {
      const sender = (call as any).peerConnection
        ?.getSenders()
        .find((s: RTCRtpSender) => s.track?.kind === 'video')
      
      if (sender) {
        sender.replaceTrack(videoTrack)
      }
    })

    if (localVideoRef.value) {
      localVideoRef.value.srcObject = localStream.value
    }
  }

  isScreenSharing.value = false
  showSnackbar('Screen sharing stopped', 'info')
}

const leaveConference = () => {
  if (confirm('Are you sure you want to leave the conference?')) {
    cleanup()
    navigateTo('/teacher/conferences')
  }
}

const cleanup = () => {
  if (localStream.value) {
    localStream.value.getTracks().forEach(track => track.stop())
  }
  
  if (screenStream.value) {
    screenStream.value.getTracks().forEach(track => track.stop())
  }

  calls.value.forEach(call => call.close())
  calls.value.clear()

  if (peer.value) {
    peer.value.destroy()
  }
}

const showSnackbar = (text: string, color: string) => {
  snackbar.value = { show: true, text, color }
}

onBeforeUnmount(() => {
  cleanup()
})
</script>

<style scoped>
.video-container {
  position: relative;
  width: 100%;
  padding-bottom: 75%; /* 4:3 Aspect Ratio */
  background: #000;
  overflow: hidden;
  border-radius: 8px;
}

.video-element {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-overlay {
  position: absolute;
  bottom: 12px;
  left: 12px;
  z-index: 1;
}
</style>