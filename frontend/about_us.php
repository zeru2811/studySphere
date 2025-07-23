
<?php 
session_start(); 
$type = "About Us";
require '../templates/template_nav.php';
require '../requires/connect.php';


?>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            },
            secondary: {
              50: '#f5f3ff',
              100: '#ede9fe',
              200: '#ddd6fe',
              300: '#c4b5fd',
              400: '#a78bfa',
              500: '#8b5cf6',
              600: '#7c3aed',
              700: '#6d28d9',
              800: '#5b21b6',
              900: '#4c1d95',
            }
          }
        }
      }
    }
  </script>




  <!-- Hero Section -->
  <div class="relative overflow-hidden">
    <div class="max-w-7xl mx-auto">
      <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
        <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 lg:mt-16 lg:px-8 xl:mt-20">
          <div class="sm:text-center lg:text-left">
            <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
              <span class="block">Redefining</span>
              <span class="block text-primary-600">Education</span>
            </h1>
            <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
              Study Sphere is transforming how the world learns. Our platform combines cutting-edge technology with proven educational methods to create personalized learning experiences.
            </p>
            <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
              <div class="rounded-md shadow">
                <a href="./courses.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 md:py-4 md:text-lg md:px-10">
                  Explore Courses
                </a>
              </div>
              <div class="mt-3 sm:mt-0 sm:ml-3">
                <a href="viber://chat?number=+959943646637" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 md:py-4 md:text-lg md:px-10">
                  Meet Our Team
                </a>
              </div>
            </div>
          </div>
          
        </main>
      </div>
    </div>
    <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
      <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1351&q=80" alt="Students learning together">
    </div>
  </div>

  <!-- Our Story -->
  <section class="py-12 bg-white overflow-hidden md:py-20 lg:py-24">
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <svg class="absolute top-0 right-0 transform -translate-y-16 translate-x-1/2 sm:translate-x-0" width="404" height="384" fill="none" viewBox="0 0 404 384" aria-hidden="true">
        <defs>
          <pattern id="64e643ad-2176-4f86-b3d7-f2c5da3b6a6d" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <rect x="0" y="0" width="4" height="4" class="text-gray-200" fill="currentColor" />
          </pattern>
        </defs>
        <rect width="404" height="384" fill="url(#64e643ad-2176-4f86-b3d7-f2c5da3b6a6d)" />
      </svg>

      <div class="relative">
        <div class="lg:grid lg:grid-flow-row-dense lg:grid-cols-2 lg:gap-8 lg:items-center">
          <div class="lg:col-start-2">
            <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              Our Story
            </h2>
            <p class="mt-3 text-lg text-gray-500">
              Founded in 2018 by a team of educators and technologists, Study Sphere began with a simple mission: to make high-quality education accessible to everyone, everywhere.
            </p>

            <div class="mt-10">
              <div class="flex">
                <div class="flex-shrink-0">
                  <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <h3 class="text-lg font-medium text-gray-900">Fast Growth</h3>
                  <p class="mt-2 text-gray-500">
                    From our first 100 students to over 2 million learners today, we've grown rapidly by staying true to our values of accessibility and excellence.
                  </p>
                </div>
              </div>

              <div class="mt-8">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Global Reach</h3>
                    <p class="mt-2 text-gray-500">
                      Our platform now serves learners in 190+ countries, with courses available in 15 languages and counting.
                    </p>
                  </div>
                </div>
              </div>

              <div class="mt-8">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Trusted Quality</h3>
                    <p class="mt-2 text-gray-500">
                      Partnered with top universities and industry leaders to deliver courses that meet the highest standards.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-10 -mx-4 relative lg:mt-0 lg:col-start-1">
            <img class="relative mx-auto rounded-lg shadow-xl" width="490" src="https://images.unsplash.com/photo-1551434678-e076c223a692?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2850&q=80" alt="Team working at office">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <div class="bg-primary-700">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:py-16 sm:px-6 lg:px-8 lg:py-20">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
          Trusted by learners worldwide
        </h2>
        <p class="mt-3 text-xl text-primary-200 sm:mt-4">
          Join millions of people who are already learning on Study Sphere.
        </p>
      </div>
      <div class="mt-10 text-center sm:max-w-3xl sm:mx-auto sm:grid sm:grid-cols-3 sm:gap-8">
        <div>
          <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full bg-primary-600 text-white">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div class="mt-5">
            <div class="text-5xl font-extrabold text-white">2M+</div>
            <div class="mt-1 text-base font-medium text-primary-200">Learners</div>
          </div>
        </div>
        <div class="mt-10 sm:mt-0">
          <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full bg-primary-600 text-white">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
            </svg>
          </div>
          <div class="mt-5">
            <div class="text-5xl font-extrabold text-white">5K+</div>
            <div class="mt-1 text-base font-medium text-primary-200">Courses</div>
          </div>
        </div>
        <div class="mt-10 sm:mt-0">
          <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full bg-primary-600 text-white">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="mt-5">
            <div class="text-5xl font-extrabold text-white">190+</div>
            <div class="mt-1 text-base font-medium text-primary-200">Countries</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Team Section -->
  <section class="py-12 bg-white sm:py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
          Meet our leadership
        </h2>
        <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
          Passionate educators, innovators, and visionaries driving Study Sphere forward.
        </p>
      </div>

      <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
        <div class="pt-6">
          <div class="flow-root bg-gray-50 rounded-lg px-6 pb-8">
            <div class="-mt-6">
              <div>
                <span class="inline-flex items-center justify-center p-3 bg-primary-500 rounded-md shadow-lg">
                  <img class="h-16 w-16 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=128&h=128&q=60" alt="Sarah Johnson">
                </span>
              </div>
              <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Sarah Johnson</h3>
              <p class="mt-1 text-base text-gray-500 text-center">CEO & Co-Founder</p>
              <p class="mt-3 text-base text-gray-500">
                Former professor with a vision for accessible education. Leads our strategic direction and partnerships.
              </p>
            </div>
          </div>
        </div>

        <div class="pt-6">
          <div class="flow-root bg-gray-50 rounded-lg px-6 pb-8">
            <div class="-mt-6">
              <div>
                <span class="inline-flex items-center justify-center p-3 bg-primary-500 rounded-md shadow-lg">
                  <img class="h-16 w-16 rounded-full" src="https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=128&h=128&q=60" alt="Michael Chen">
                </span>
              </div>
              <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Michael Chen</h3>
              <p class="mt-1 text-base text-gray-500 text-center">CTO & Co-Founder</p>
              <p class="mt-3 text-base text-gray-500">
                Tech visionary who built our adaptive learning platform from the ground up.
              </p>
            </div>
          </div>
        </div>

        <div class="pt-6">
          <div class="flow-root bg-gray-50 rounded-lg px-6 pb-8">
            <div class="-mt-6">
              <div>
                <span class="inline-flex items-center justify-center p-3 bg-primary-500 rounded-md shadow-lg">
                  <img class="h-16 w-16 rounded-full" src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=128&h=128&q=60" alt="David Rodriguez">
                </span>
              </div>
              <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">David Rodriguez</h3>
              <p class="mt-1 text-base text-gray-500 text-center">Chief Learning Officer</p>
              <p class="mt-3 text-base text-gray-500">
                Education expert ensuring our pedagogy meets the highest standards.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Values -->
  <section class="py-12 bg-gray-50 sm:py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
          Our Core Values
        </h2>
        <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
          The principles that guide everything we do at Study Sphere.
        </p>
      </div>

      <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
        <div class="pt-6">
          <div class="flow-root bg-white rounded-lg px-6 pb-8 shadow-lg h-full">
            <div class="-mt-6">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white mx-auto">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Accessibility</h3>
              <p class="mt-3 text-base text-gray-500">
                We believe education should be available to everyone, regardless of location, background, or financial situation.
              </p>
            </div>
          </div>
        </div>

        <div class="pt-6">
          <div class="flow-root bg-white rounded-lg px-6 pb-8 shadow-lg h-full">
            <div class="-mt-6">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white mx-auto">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Innovation</h3>
              <p class="mt-3 text-base text-gray-500">
                We constantly push boundaries to create learning experiences that are engaging, effective, and transformative.
              </p>
            </div>
          </div>
        </div>

        <div class="pt-6">
          <div class="flow-root bg-white rounded-lg px-6 pb-8 shadow-lg h-full">
            <div class="-mt-6">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white mx-auto">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">Innovation</h3>
              <p class="mt-3 text-base text-gray-500">
                We constantly push boundaries to create learning experiences that are engaging, effective, and transformative.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="py-20 bg-white px-6">
    <div class="max-w-4xl mx-auto">
      <h2 class="text-4xl font-bold text-center mb-12">Frequently Asked Questions</h2>
      <div class="space-y-6">
        <details class="p-4 border rounded-md">
          <summary class="font-semibold cursor-pointer">How do I join as a student?</summary>
          <p class="mt-2 text-gray-700">Just click the "Join" button on our homepage and register with your email to get started.</p>
        </details>
        <details class="p-4 border rounded-md">
          <summary class="font-semibold cursor-pointer">Is there a fee for using StudySphere?</summary>
          <p class="mt-2 text-gray-700">We offer both free and premium plans. Internal students (via partnered institutions) may get discounts or full access.</p>
        </details>
        <details class="p-4 border rounded-md">
          <summary class="font-semibold cursor-pointer">How can I become a tutor?</summary>
          <p class="mt-2 text-gray-700">Apply through the “Become a Tutor” form. Once reviewed, our team will reach out within 48 hours.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- Call to Action -->
  <section class="py-20 bg-indigo-700 text-white text-center px-6">
    <h2 class="text-4xl font-bold mb-4">Ready to Explore, Learn, and Grow?</h2>
    <p class="text-lg mb-6">Join thousands of students and teachers shaping the future of education with StudySphere.</p>
    <a href="/signup" class="inline-block bg-white text-indigo-700 px-6 py-3 font-semibold rounded-full shadow hover:shadow-lg transition">Get Started Now</a>
  </section>



  <?php require '../templates/template_footer.php'  ?>