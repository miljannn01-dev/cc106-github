export default function Home() {
  return (
    <div style={{
      margin: 0,
      padding: 0,
      fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
      background: "linear-gradient(135deg, #f5f1fa 0%, #e8ddf5 100%)",
      minHeight: "100vh",
      display: "flex",
      alignItems: "center",
      justifyContent: "center"
    }}>
      <style>{`
        @keyframes slideUp {
          from { opacity: 0; transform: translateY(30px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .welcome-container {
          animation: slideUp 0.6s ease-out;
        }
      `}</style>
      
      <div className="welcome-container" style={{
        background: "white",
        padding: "60px 40px",
        borderRadius: "20px",
        boxShadow: "0 10px 40px rgba(200, 150, 230, 0.15)",
        textAlign: "center",
        maxWidth: "650px",
        width: "90%"
      }}>
        <div style={{ fontSize: "52px", marginBottom: "20px" }}>💼</div>
        
        <h1 style={{
          fontSize: "38px",
          marginBottom: "12px",
          color: "#5a3fa3",
          fontWeight: "700"
        }}>Grant Hub</h1>
        
        <p style={{
          color: "#7d6b8f",
          fontSize: "16px",
          marginBottom: "40px",
          lineHeight: "1.6"
        }}>
          Empowering startups through strategic funding. Discover opportunities, apply with ease, and grow your business.
        </p>
        
        <div style={{
          display: "flex",
          gap: "15px",
          justifyContent: "center",
          flexWrap: "wrap",
          marginBottom: "50px"
        }}>
          <a href="/public/auth/login.html" style={{
            padding: "14px 35px",
            border: "none",
            borderRadius: "12px",
            fontSize: "16px",
            fontWeight: "600",
            cursor: "pointer",
            textDecoration: "none",
            display: "inline-block",
            background: "linear-gradient(135deg, #d4a5e8 0%, #c68edb 100%)",
            color: "white",
            boxShadow: "0 5px 20px rgba(212, 165, 232, 0.3)",
            transition: "all 0.3s ease"
          }} onMouseEnter={(e) => {
            e.target.style.transform = "translateY(-3px)";
            e.target.style.boxShadow = "0 8px 30px rgba(212, 165, 232, 0.4)";
          }} onMouseLeave={(e) => {
            e.target.style.transform = "translateY(0)";
            e.target.style.boxShadow = "0 5px 20px rgba(212, 165, 232, 0.3)";
          }}>
            Login
          </a>
          
          <a href="/public/auth/register.html" style={{
            padding: "14px 35px",
            border: "2px solid #d4a5e8",
            borderRadius: "12px",
            fontSize: "16px",
            fontWeight: "600",
            cursor: "pointer",
            textDecoration: "none",
            display: "inline-block",
            background: "white",
            color: "#c68edb",
            transition: "all 0.3s ease"
          }} onMouseEnter={(e) => {
            e.target.style.background = "#f3e9f8";
            e.target.style.transform = "translateY(-3px)";
          }} onMouseLeave={(e) => {
            e.target.style.background = "white";
            e.target.style.transform = "translateY(0)";
          }}>
            Register
          </a>
        </div>

        <div style={{
          display: "grid",
          gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))",
          gap: "20px",
          marginTop: "40px"
        }}>
          {[
            { icon: "🚀", title: "For Startups", desc: "Discover and apply for tailored opportunities" },
            { icon: "👨‍💼", title: "For Admins", desc: "Create and manage grant programs" },
            { icon: "📊", title: "Real-time", desc: "Track applications instantly" },
            { icon: "🔒", title: "Secure", desc: "Your data is always protected" }
          ].map((feature, idx) => (
            <div key={idx} style={{
              padding: "25px",
              background: "linear-gradient(135deg, #f9f5fc 0%, #f3e9f8 100%)",
              borderRadius: "15px",
              border: "1px solid #e8d9f0",
              transition: "all 0.3s ease",
              cursor: "pointer"
            }} onMouseEnter={(e) => {
              e.currentTarget.style.transform = "translateY(-5px)";
              e.currentTarget.style.boxShadow = "0 10px 25px rgba(212, 165, 232, 0.15)";
            }} onMouseLeave={(e) => {
              e.currentTarget.style.transform = "translateY(0)";
              e.currentTarget.style.boxShadow = "none";
            }}>
              <div style={{ fontSize: "32px", marginBottom: "10px" }}>{feature.icon}</div>
              <div style={{
                fontWeight: "600",
                color: "#5a3fa3",
                marginBottom: "8px",
                fontSize: "15px"
              }}>{feature.title}</div>
              <div style={{
                fontSize: "13px",
                color: "#7d6b8f"
              }}>{feature.desc}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
