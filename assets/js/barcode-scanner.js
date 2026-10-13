// Barcode Scanner Integration
// Supports USB/Wireless scanners and camera-based scanning

class BarcodeScanner {
    constructor(inputCallback) {
        this.buffer = '';
        this.timeout = null;
        this.inputCallback = inputCallback;
        this.scanDelay = 50; // milliseconds between keystrokes for scanner detection
        this.enabled = true;
        
        this.initKeyboardScanner();
    }
    
    // Initialize keyboard-based scanner (USB/Wireless)
    initKeyboardScanner() {
        document.addEventListener('keypress', (e) => {
            if (!this.enabled) return;
            
            // Ignore if typing in input fields (except barcode search field)
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                if (!e.target.classList.contains('barcode-input')) {
                    return;
                }
            }
            
            clearTimeout(this.timeout);
            
            // Enter key means end of barcode
            if (e.key === 'Enter') {
                if (this.buffer.length > 3) { // Valid barcode minimum length
                    this.processBarcode(this.buffer);
                }
                this.buffer = '';
                return;
            }
            
            // Add character to buffer
            this.buffer += e.key;
            
            // Auto-submit after delay (scanner sends all chars quickly)
            this.timeout = setTimeout(() => {
                if (this.buffer.length > 3) {
                    this.processBarcode(this.buffer);
                }
                this.buffer = '';
            }, this.scanDelay);
        });
    }
    
    processBarcode(barcode) {
        console.log('Barcode scanned:', barcode);
        if (this.inputCallback) {
            this.inputCallback(barcode);
        }
        
        // Play beep sound
        this.playBeep();
    }
    
    playBeep() {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    }
    
    enable() {
        this.enabled = true;
    }
    
    disable() {
        this.enabled = false;
    }
    
    // Camera-based barcode scanning using HTML5 getUserMedia
    async initCameraScanner(videoElement) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            videoElement.srcObject = stream;
            return stream;
        } catch (error) {
            console.error('Camera access denied:', error);
            throw error;
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BarcodeScanner;
}
