import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Composable for enhanced touch interactions
 * Provides touch gesture recognition, pressure sensitivity, and mobile optimizations
 */
export function useTouchInteractions(element, options = {}) {
  const {
    enablePressure = true,
    enableGestures = true,
    touchDelay = 16, // ~60fps
    pressureMultiplier = 1,
    smoothing = true,
    preventDefault = true
  } = options

  // Touch state
  const isTouch = ref(false)
  const touchCount = ref(0)
  const lastTouchTime = ref(0)
  const touchStartPos = ref({ x: 0, y: 0 })
  const touchCurrentPos = ref({ x: 0, y: 0 })
  const touchPressure = ref(1)
  const touchVelocity = ref({ x: 0, y: 0 })

  // Gesture state
  const isSwipe = ref(false)
  const isPinch = ref(false)
  const swipeDirection = ref(null)
  const pinchScale = ref(1)
  const initialPinchDistance = ref(0)

  // Touch event handlers
  const handleTouchStart = (event) => {
    if (preventDefault) event.preventDefault()
    
    const now = Date.now()
    if (now - lastTouchTime.value < touchDelay) return
    lastTouchTime.value = now

    isTouch.value = true
    touchCount.value = event.touches.length
    
    const touch = event.touches[0]
    const rect = element.value?.getBoundingClientRect()
    if (!rect) return

    const x = touch.clientX - rect.left
    const y = touch.clientY - rect.top
    
    touchStartPos.value = { x, y }
    touchCurrentPos.value = { x, y }
    
    // Enhanced pressure detection
    if (enablePressure) {
      touchPressure.value = (touch.force || touch.webkitForce || 1) * pressureMultiplier
    }

    // Multi-touch gesture detection
    if (enableGestures && touchCount.value === 2) {
      const touch2 = event.touches[1]
      const distance = Math.sqrt(
        Math.pow(touch2.clientX - touch.clientX, 2) +
        Math.pow(touch2.clientY - touch.clientY, 2)
      )
      initialPinchDistance.value = distance
      isPinch.value = true
    }

    // Emit touch start event
    emitTouchEvent('touchstart', {
      position: { x, y },
      pressure: touchPressure.value,
      touchCount: touchCount.value,
      originalEvent: event
    })
  }

  const handleTouchMove = (event) => {
    if (preventDefault) event.preventDefault()
    if (!isTouch.value) return

    const now = Date.now()
    if (now - lastTouchTime.value < touchDelay) return
    lastTouchTime.value = now

    const touch = event.touches[0]
    const rect = element.value?.getBoundingClientRect()
    if (!rect) return

    const x = touch.clientX - rect.left
    const y = touch.clientY - rect.top
    
    // Calculate velocity
    const deltaTime = now - lastTouchTime.value
    if (deltaTime > 0) {
      touchVelocity.value = {
        x: (x - touchCurrentPos.value.x) / deltaTime,
        y: (y - touchCurrentPos.value.y) / deltaTime
      }
    }

    touchCurrentPos.value = { x, y }
    
    // Update pressure
    if (enablePressure) {
      touchPressure.value = (touch.force || touch.webkitForce || 1) * pressureMultiplier
    }

    // Gesture detection
    if (enableGestures) {
      // Swipe detection
      const deltaX = x - touchStartPos.value.x
      const deltaY = y - touchStartPos.value.y
      const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY)
      
      if (distance > 30 && !isPinch.value) {
        isSwipe.value = true
        
        // Determine swipe direction
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
          swipeDirection.value = deltaX > 0 ? 'right' : 'left'
        } else {
          swipeDirection.value = deltaY > 0 ? 'down' : 'up'
        }
      }

      // Pinch detection
      if (touchCount.value === 2 && isPinch.value) {
        const touch2 = event.touches[1]
        const currentDistance = Math.sqrt(
          Math.pow(touch2.clientX - touch.clientX, 2) +
          Math.pow(touch2.clientY - touch.clientY, 2)
        )
        pinchScale.value = currentDistance / initialPinchDistance.value
      }
    }

    // Emit touch move event
    emitTouchEvent('touchmove', {
      position: { x, y },
      pressure: touchPressure.value,
      velocity: touchVelocity.value,
      touchCount: touchCount.value,
      isSwipe: isSwipe.value,
      swipeDirection: swipeDirection.value,
      isPinch: isPinch.value,
      pinchScale: pinchScale.value,
      originalEvent: event
    })
  }

  const handleTouchEnd = (event) => {
    if (preventDefault) event.preventDefault()

    const wasSwipe = isSwipe.value
    const wasPinch = isPinch.value
    const finalSwipeDirection = swipeDirection.value
    const finalPinchScale = pinchScale.value

    // Reset touch state
    isTouch.value = false
    touchCount.value = event.touches.length
    isSwipe.value = false
    isPinch.value = false
    swipeDirection.value = null
    pinchScale.value = 1
    touchPressure.value = 1
    touchVelocity.value = { x: 0, y: 0 }

    // Emit touch end event
    emitTouchEvent('touchend', {
      position: touchCurrentPos.value,
      wasSwipe,
      swipeDirection: finalSwipeDirection,
      wasPinch,
      pinchScale: finalPinchScale,
      touchCount: touchCount.value,
      originalEvent: event
    })
  }

  // Event emission
  const touchEventCallbacks = ref({})
  
  const emitTouchEvent = (eventType, data) => {
    const callback = touchEventCallbacks.value[eventType]
    if (callback) {
      callback(data)
    }
  }

  const on = (eventType, callback) => {
    touchEventCallbacks.value[eventType] = callback
  }

  const off = (eventType) => {
    delete touchEventCallbacks.value[eventType]
  }

  // Touch utilities
  const getTouchPosition = (event) => {
    const touch = event.touches[0] || event.changedTouches[0]
    const rect = element.value?.getBoundingClientRect()
    if (!rect || !touch) return { x: 0, y: 0 }

    return {
      x: touch.clientX - rect.left,
      y: touch.clientY - rect.top
    }
  }

  const getRelativeTouchPosition = (event) => {
    const pos = getTouchPosition(event)
    const rect = element.value?.getBoundingClientRect()
    if (!rect) return { x: 0, y: 0 }

    return {
      x: pos.x / rect.width,
      y: pos.y / rect.height
    }
  }

  const isTouchDevice = () => {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0
  }

  const supportsForce = () => {
    return 'ontouchforcechange' in window
  }

  // Haptic feedback (if supported)
  const vibrate = (pattern = 50) => {
    if ('vibrate' in navigator) {
      navigator.vibrate(pattern)
    }
  }

  // Setup and cleanup
  onMounted(() => {
    if (element.value && isTouchDevice()) {
      element.value.addEventListener('touchstart', handleTouchStart, { passive: false })
      element.value.addEventListener('touchmove', handleTouchMove, { passive: false })
      element.value.addEventListener('touchend', handleTouchEnd, { passive: false })
      element.value.addEventListener('touchcancel', handleTouchEnd, { passive: false })
    }
  })

  onUnmounted(() => {
    if (element.value) {
      element.value.removeEventListener('touchstart', handleTouchStart)
      element.value.removeEventListener('touchmove', handleTouchMove)
      element.value.removeEventListener('touchend', handleTouchEnd)
      element.value.removeEventListener('touchcancel', handleTouchEnd)
    }
  })

  return {
    // State
    isTouch: readonly(isTouch),
    touchCount: readonly(touchCount),
    touchPressure: readonly(touchPressure),
    touchVelocity: readonly(touchVelocity),
    isSwipe: readonly(isSwipe),
    isPinch: readonly(isPinch),
    swipeDirection: readonly(swipeDirection),
    pinchScale: readonly(pinchScale),

    // Event handling
    on,
    off,

    // Utilities
    getTouchPosition,
    getRelativeTouchPosition,
    isTouchDevice,
    supportsForce,
    vibrate,

    // Current position
    currentPosition: readonly(touchCurrentPos)
  }
}

/**
 * Composable for mobile-optimized drag and drop
 */
export function useMobileDragDrop(element, options = {}) {
  const {
    dragThreshold = 10,
    enableHapticFeedback = true,
    dragClass = 'dragging',
    dropZoneClass = 'drop-zone-active'
  } = options

  const isDragging = ref(false)
  const dragStartPos = ref({ x: 0, y: 0 })
  const dragCurrentPos = ref({ x: 0, y: 0 })
  const dragOffset = ref({ x: 0, y: 0 })

  const touchInteractions = useTouchInteractions(element, {
    enableGestures: false,
    preventDefault: false
  })

  let dragElement = null
  let dropZones = []

  const startDrag = (position) => {
    isDragging.value = true
    dragStartPos.value = position
    dragCurrentPos.value = position
    
    if (element.value) {
      element.value.classList.add(dragClass)
      
      // Create drag preview
      dragElement = element.value.cloneNode(true)
      dragElement.style.position = 'fixed'
      dragElement.style.pointerEvents = 'none'
      dragElement.style.zIndex = '9999'
      dragElement.style.opacity = '0.8'
      dragElement.style.transform = 'scale(1.05)'
      document.body.appendChild(dragElement)
      
      // Haptic feedback
      if (enableHapticFeedback) {
        touchInteractions.vibrate(50)
      }
    }

    // Find drop zones
    dropZones = Array.from(document.querySelectorAll('[data-drop-zone]'))
  }

  const updateDrag = (position) => {
    if (!isDragging.value) return

    dragCurrentPos.value = position
    dragOffset.value = {
      x: position.x - dragStartPos.value.x,
      y: position.y - dragStartPos.value.y
    }

    // Update drag element position
    if (dragElement) {
      dragElement.style.left = `${position.x - dragElement.offsetWidth / 2}px`
      dragElement.style.top = `${position.y - dragElement.offsetHeight / 2}px`
    }

    // Check drop zones
    const elementUnderTouch = document.elementFromPoint(position.x, position.y)
    dropZones.forEach(zone => {
      if (zone.contains(elementUnderTouch)) {
        zone.classList.add(dropZoneClass)
      } else {
        zone.classList.remove(dropZoneClass)
      }
    })
  }

  const endDrag = (position) => {
    if (!isDragging.value) return

    isDragging.value = false
    
    // Clean up
    if (element.value) {
      element.value.classList.remove(dragClass)
    }
    
    if (dragElement) {
      document.body.removeChild(dragElement)
      dragElement = null
    }

    // Check for drop
    const elementUnderTouch = document.elementFromPoint(position.x, position.y)
    const dropZone = dropZones.find(zone => zone.contains(elementUnderTouch))
    
    if (dropZone) {
      // Emit drop event
      const dropEvent = new CustomEvent('mobile-drop', {
        detail: {
          dragElement: element.value,
          dropZone,
          position,
          offset: dragOffset.value
        }
      })
      dropZone.dispatchEvent(dropEvent)
      
      // Haptic feedback for successful drop
      if (enableHapticFeedback) {
        touchInteractions.vibrate([50, 50, 50])
      }
    }

    // Clean up drop zones
    dropZones.forEach(zone => {
      zone.classList.remove(dropZoneClass)
    })
    dropZones = []
  }

  // Set up touch interactions
  touchInteractions.on('touchstart', ({ position }) => {
    dragStartPos.value = position
  })

  touchInteractions.on('touchmove', ({ position }) => {
    const distance = Math.sqrt(
      Math.pow(position.x - dragStartPos.value.x, 2) +
      Math.pow(position.y - dragStartPos.value.y, 2)
    )

    if (!isDragging.value && distance > dragThreshold) {
      startDrag(position)
    } else if (isDragging.value) {
      updateDrag(position)
    }
  })

  touchInteractions.on('touchend', ({ position }) => {
    if (isDragging.value) {
      endDrag(position)
    }
  })

  return {
    isDragging: readonly(isDragging),
    dragOffset: readonly(dragOffset),
    ...touchInteractions
  }
}

export default useTouchInteractions