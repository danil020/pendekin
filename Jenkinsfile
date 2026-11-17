pipeline {
    agent any

    environment {
        IMAGE = 'nielldd/pendekin-app'   
        TAG   = "${env.BUILD_NUMBER}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
                echo '✅ Checkout berhasil'
            }
        }

        stage('Test') {
            steps {
                echo '🧪 Menjalankan PHPUnit di dalam container...'
                sh 'docker build --target test -t pendekin-test .'
                sh 'docker run --rm pendekin-test'
            }
        }

        stage('Build Image') {
            steps {
                echo '🔨 Building production image...'
                sh "docker build --target production -t ${IMAGE}:${TAG} -t ${IMAGE}:latest ."
            }
        }

        stage('Push Image') {
            steps {
                echo '📤 Push ke Docker Hub...'
                withCredentials([usernamePassword(
                    credentialsId: 'dockerhub-credentials',
                    usernameVariable: 'USER',
                    passwordVariable: 'PASS'
                )]) {
                    sh 'echo $PASS | docker login -u $USER --password-stdin'
                    sh "docker push ${IMAGE}:${TAG}"
                    sh "docker push ${IMAGE}:latest"
                    sh 'docker logout'
                }
            }
        }

        stage('Deploy') {
            steps {
                echo '🚀 Deploy via Docker Compose...'
                sh 'docker compose pull app'
                sh 'docker compose up -d'
                sh 'sleep 20'
            }
        }

        stage('Smoke Test') {
            steps {
                echo '🔍 Smoke Test...'
                // sh 'curl -fsS http://localhost:8000/health'

                script {
                    def code = sh(
                        script: "curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/r/test",
                        returnStdout: true
                    ).trim()
                    if (code != '302') {
                        error("❌ Smoke Test gagal: expected 302, got ${code}")
                    }
                    echo "✅ Redirect OK (${code})"
                }
            }
        }
    }

    post {
        success {
            echo '🎉 Pipeline SUKSES: Pendekin ter-deploy!'
        }
        failure {
            echo '❌ Pipeline GAGAL: cek stage merah. Versi lama tetap jalan.'
        }
        always {
            echo '📊 Pipeline selesai.'
        }
    }
}